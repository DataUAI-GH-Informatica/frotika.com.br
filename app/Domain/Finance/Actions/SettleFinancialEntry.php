<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Models\BankAccount;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Tenancy\Models\Company;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Dá baixa (liquida) um lançamento previsto: define a conta, a data de pagamento
 * e o meio. Serve tanto para lançamentos manuais quanto para receitas de CT-e —
 * a realização é fato de caixa, não altera o dado de origem.
 */
final class SettleFinancialEntry
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly RecalculateBankAccountCurrentBalance $recalculateBankAccountCurrentBalance,
    ) {}

    /**
     * @param  array{
     *     bank_account_id: int,
     *     paid_at: string,
     *     payment_method?: string|null,
     *     paid_amount_cents?: int|null,
     *     discount_cents?: int|null,
     *     interest_cents?: int|null
     * }  $data
     * @return array{is_partial: bool, remaining_entry_id: int|null}
     */
    public function execute(Company $company, int $entryId, array $data): array
    {
        $validated = Validator::make($data, [
            'bank_account_id' => ['required', 'integer', 'min:1'],
            'paid_at' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'paid_amount_cents' => ['nullable', 'integer', 'min:1'],
            'discount_cents' => ['nullable', 'integer', 'min:0'],
            'interest_cents' => ['nullable', 'integer', 'min:0'],
        ])->validate();

        return $this->tenant->runFor($company, function () use ($validated, $entryId, $company): array {
            $entry = FinancialEntry::query()->find($entryId);

            if ($entry === null) {
                throw ValidationException::withMessages([
                    'entry_id' => 'Lançamento financeiro inválido para a empresa ativa.',
                ]);
            }

            if ($entry->transfer_pair_id !== null) {
                throw ValidationException::withMessages([
                    'entry_id' => 'Transferências já nascem liquidadas e não recebem baixa.',
                ]);
            }

            if ($entry->status === FinancialEntryStatus::Canceled) {
                throw ValidationException::withMessages([
                    'entry_id' => 'Lançamento cancelado não pode receber baixa.',
                ]);
            }

            if ($entry->status === FinancialEntryStatus::Settled) {
                throw ValidationException::withMessages([
                    'entry_id' => 'Lançamento já está liquidado.',
                ]);
            }

            $bankAccount = BankAccount::query()->find($validated['bank_account_id']);

            if ($bankAccount === null || ! $bankAccount->active) {
                throw ValidationException::withMessages([
                    'bank_account_id' => 'Conta bancária inválida para a empresa ativa.',
                ]);
            }

            $discountCents = (int) ($validated['discount_cents'] ?? 0);
            $interestCents = (int) ($validated['interest_cents'] ?? 0);
            $effectiveAmountCents = (int) $entry->amount_cents - $discountCents + $interestCents;

            if ($effectiveAmountCents <= 0) {
                throw ValidationException::withMessages([
                    'discount_cents' => 'Desconto e juros resultaram em valor final invalido para baixa.',
                ]);
            }

            $paidAmountCents = (int) ($validated['paid_amount_cents'] ?? $effectiveAmountCents);

            if ($paidAmountCents > $effectiveAmountCents) {
                throw ValidationException::withMessages([
                    'paid_amount_cents' => 'Baixa parcial nao pode ser maior que o valor final do lancamento.',
                ]);
            }

            $isPartial = $paidAmountCents < $effectiveAmountCents;
            $remainingEntryId = null;

            DB::transaction(function () use (&$remainingEntryId, $entry, $validated, $paidAmountCents, $effectiveAmountCents, $discountCents, $interestCents, $isPartial): void {
                $entry->forceFill([
                    'status' => FinancialEntryStatus::Settled->value,
                    'paid_at' => $validated['paid_at'],
                    'bank_account_id' => $validated['bank_account_id'],
                    'payment_method' => $validated['payment_method'] ?? null,
                    'amount_cents' => $paidAmountCents,
                    'settlement_discount_cents' => $discountCents,
                    'settlement_interest_cents' => $interestCents,
                    'reconciled_at' => null,
                ])->save();

                if ($isPartial) {
                    $remaining = FinancialEntry::query()->create([
                        'company_id' => $entry->company_id,
                        'bank_account_id' => null,
                        'financial_category_id' => $entry->financial_category_id,
                        'vehicle_id' => $entry->vehicle_id,
                        'driver_id' => $entry->driver_id,
                        'trip_id' => $entry->trip_id,
                        'type' => $entry->type->value,
                        'description' => $entry->description,
                        'document_number' => $entry->document_number,
                        'competence_date' => $entry->competence_date->toDateString(),
                        'reference_date' => $entry->reference_date?->toDateString() ?? $entry->competence_date->toDateString(),
                        'due_date' => $entry->due_date?->toDateString(),
                        'paid_at' => null,
                        'amount_cents' => $effectiveAmountCents - $paidAmountCents,
                        'settlement_discount_cents' => 0,
                        'settlement_interest_cents' => 0,
                        'status' => FinancialEntryStatus::Forecast->value,
                        'payment_method' => null,
                        'sourceable_type' => null,
                        'sourceable_id' => null,
                        'transfer_pair_id' => null,
                        'recurrence_id' => $entry->recurrence_id,
                        'installment_number' => $entry->installment_number,
                        'installment_total' => $entry->installment_total,
                        'attachment_path' => $entry->attachment_path,
                        'reconciled_at' => null,
                        'created_by' => $entry->created_by,
                    ]);

                    $remainingEntryId = (int) $remaining->getKey();
                }
            });

            $this->recalculateBankAccountCurrentBalance->execute($company, (int) $validated['bank_account_id']);

            return [
                'is_partial' => $isPartial,
                'remaining_entry_id' => $remainingEntryId,
            ];
        });
    }
}
