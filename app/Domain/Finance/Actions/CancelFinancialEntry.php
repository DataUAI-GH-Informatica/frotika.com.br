<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\FinancialEntryCancelScope;
use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Finance\Models\Recurrence;
use App\Domain\Tenancy\Models\Company;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelFinancialEntry
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly RecalculateBankAccountCurrentBalance $recalculateBankAccountCurrentBalance,
    ) {}

    public function execute(Company $company, int $entryId, string $scope = FinancialEntryCancelScope::Single->value): void
    {
        $this->tenant->runFor($company, function () use ($entryId, $company, $scope): void {
            $entry = FinancialEntry::query()->find($entryId);

            if ($entry === null) {
                throw ValidationException::withMessages([
                    'entry_id' => 'Lancamento financeiro invalido para a empresa ativa.',
                ]);
            }

            if (! in_array($scope, [
                FinancialEntryCancelScope::Single->value,
                FinancialEntryCancelScope::Forward->value,
                FinancialEntryCancelScope::All->value,
            ], true)) {
                throw ValidationException::withMessages([
                    'scope' => 'Escopo de cancelamento invalido.',
                ]);
            }

            DB::transaction(function () use ($entry, $scope, $company): void {
                $entriesToCancel = $this->resolveEntriesToCancel($entry, $scope);
                $affectedBankAccountIds = [];

                foreach ($entriesToCancel as $entryToCancel) {
                    $entryAffectedBankAccounts = $this->cancelSingleEntry($entryToCancel);

                    foreach ($entryAffectedBankAccounts as $bankAccountId) {
                        $affectedBankAccountIds[] = $bankAccountId;
                    }
                }

                $this->adjustRecurrenceAfterScopedCancel($entry, $scope);

                foreach (array_values(array_unique($affectedBankAccountIds)) as $bankAccountId) {
                    $this->recalculateBankAccountCurrentBalance->execute($company, $bankAccountId);
                }
            });
        });
    }

    /**
     * @return Collection<int, FinancialEntry>
     */
    private function resolveEntriesToCancel(FinancialEntry $entry, string $scope): Collection
    {
        if ($scope === FinancialEntryCancelScope::Single->value) {
            return new Collection([$entry]);
        }

        if ($entry->recurrence_id === null) {
            throw ValidationException::withMessages([
                'scope' => 'Cancelamento em lote exige lancamento vinculado a recorrencia ou parcelamento.',
            ]);
        }

        $query = FinancialEntry::query()
            ->where('recurrence_id', (int) $entry->recurrence_id)
            ->where('status', '<>', FinancialEntryStatus::Canceled->value)
            ->orderBy('reference_date')
            ->orderBy('id');

        if ($scope === FinancialEntryCancelScope::Forward->value) {
            $referenceDate = $entry->reference_date?->toDateString() ?? $entry->competence_date->toDateString();
            $query->whereDate('reference_date', '>=', $referenceDate);
        }

        return $query->get();
    }

    /**
     * @return list<int>
     */
    private function cancelSingleEntry(FinancialEntry $entry): array
    {
        if ($entry->sourceable_type !== null || $entry->sourceable_id !== null) {
            throw ValidationException::withMessages([
                'entry_id' => 'Lancamentos sincronizados devem ser cancelados na origem.',
            ]);
        }

        $pairEntry = null;

        if ($entry->transfer_pair_id !== null) {
            $pairEntry = FinancialEntry::query()->find((int) $entry->transfer_pair_id);

            if ($pairEntry === null) {
                throw ValidationException::withMessages([
                    'transfer_pair_id' => 'Par da transferencia nao encontrado para a empresa ativa.',
                ]);
            }

            if ($pairEntry->sourceable_type !== null || $pairEntry->sourceable_id !== null) {
                throw ValidationException::withMessages([
                    'transfer_pair_id' => 'Transferencias sincronizadas devem ser canceladas na origem.',
                ]);
            }
        }

        if ($entry->status === FinancialEntryStatus::Canceled && ($pairEntry === null || $pairEntry->status === FinancialEntryStatus::Canceled)) {
            return [];
        }

        $previousBankAccountId = $entry->bank_account_id !== null ? (int) $entry->bank_account_id : null;
        $previousPairBankAccountId = $pairEntry?->bank_account_id !== null ? (int) $pairEntry->bank_account_id : null;

        $entry->forceFill([
            'status' => FinancialEntryStatus::Canceled,
            'paid_at' => null,
            'bank_account_id' => null,
            'payment_method' => null,
            'reconciled_at' => null,
        ])->save();

        if ($pairEntry !== null && $pairEntry->status !== FinancialEntryStatus::Canceled) {
            $pairEntry->forceFill([
                'status' => FinancialEntryStatus::Canceled,
                'paid_at' => null,
                'bank_account_id' => null,
                'payment_method' => null,
                'reconciled_at' => null,
            ])->save();
        }

        return array_values(array_filter([
            $previousBankAccountId,
            $previousPairBankAccountId,
        ], static fn ($bankAccountId): bool => $bankAccountId !== null));
    }

    private function adjustRecurrenceAfterScopedCancel(FinancialEntry $entry, string $scope): void
    {
        if ($entry->recurrence_id === null || $scope === FinancialEntryCancelScope::Single->value) {
            return;
        }

        $recurrence = Recurrence::query()->find((int) $entry->recurrence_id);

        if (! $recurrence instanceof Recurrence) {
            return;
        }

        if ($scope === FinancialEntryCancelScope::All->value) {
            $recurrence->forceFill([
                'active' => false,
            ])->save();

            return;
        }

        $previousDay = ($entry->reference_date ?? $entry->competence_date)->copy()->subDay()->toDateString();

        if ($previousDay < $recurrence->starts_at->toDateString()) {
            $recurrence->forceFill([
                'active' => false,
                'ends_at' => $entry->reference_date?->toDateString() ?? $entry->competence_date->toDateString(),
            ])->save();

            return;
        }

        $recurrence->forceFill([
            'ends_at' => $previousDay,
        ])->save();
    }
}
