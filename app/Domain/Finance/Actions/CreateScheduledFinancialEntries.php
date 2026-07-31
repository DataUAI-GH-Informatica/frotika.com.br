<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Enums\RecurrenceKind;
use App\Domain\Finance\Models\FinancialEntry;
use App\Domain\Finance\Models\Recurrence;
use App\Domain\Tenancy\Models\Company;
use App\Support\Money\Apportionment;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateScheduledFinancialEntries
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly CreateRecurrence $createRecurrence,
        private readonly GenerateForecastEntriesFromRecurrences $generateForecastEntriesFromRecurrences,
    ) {}

    /**
     * @param  array{
     *     financial_category_id: int,
     *     type: string,
     *     description: string,
     *     amount_cents: int,
     *     competence_date?: string|null,
     *     reference_date: string,
     *     document_number?: string|null,
     *     vehicle_id?: int|null,
     *     driver_id?: int|null,
     *     trip_id?: int|null,
     *     payment_method?: string|null,
     *     installments?: int|null,
     *     launch_mode: string,
     *     status?: string
     * }  $data
     * @return array{recurrence_id: int, entries_created: int}
     */
    public function execute(Company $company, int $createdBy, array $data): array
    {
        $launchMode = $data['launch_mode'];

        if (! in_array($launchMode, ['monthly', 'installment'], true)) {
            throw ValidationException::withMessages([
                'launch_mode' => 'Modo de agendamento invalido.',
            ]);
        }

        if (($data['status'] ?? 'forecast') !== 'forecast') {
            throw ValidationException::withMessages([
                'status' => 'Recorrencias e parcelamentos devem ser criados como previstos.',
            ]);
        }

        $referenceDate = CarbonImmutable::parse($data['reference_date'])->toDateString();
        $installments = $launchMode === 'installment' ? (int) ($data['installments'] ?? 0) : null;

        if ($launchMode === 'installment' && $installments < 2) {
            throw ValidationException::withMessages([
                'installments' => 'Parcelamento exige no minimo 2 parcelas.',
            ]);
        }

        if ($launchMode === 'installment' && ($data['competence_date'] ?? null) === null) {
            throw ValidationException::withMessages([
                'competence_date' => 'Parcelamento exige data do servico fixa.',
            ]);
        }

        $recurrenceId = $this->createRecurrence->execute($company, $createdBy, [
            'financial_category_id' => $data['financial_category_id'],
            'type' => $data['type'],
            'description' => $data['description'],
            'document_number' => $data['document_number'] ?? null,
            'amount_cents' => $data['amount_cents'],
            'frequency' => 'monthly',
            'kind' => $launchMode === 'installment' ? RecurrenceKind::Installment->value : RecurrenceKind::Recurring->value,
            'day_of_month' => (int) CarbonImmutable::parse($referenceDate)->day,
            'starts_at' => $referenceDate,
            'ends_at' => null,
            'fixed_competence_date' => $launchMode === 'installment'
                ? ($data['competence_date'] ?? null)
                : null,
            'installments' => $installments,
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'driver_id' => $data['driver_id'] ?? null,
            'trip_id' => $data['trip_id'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'active' => true,
        ]);

        if ($launchMode === 'installment') {
            return $this->createInstallments($company, $createdBy, $data, $recurrenceId, $installments, $referenceDate);
        }

        $generationReferenceDate = $launchMode === 'installment'
            ? CarbonImmutable::parse($referenceDate)->addMonths($installments - 1)->toDateString()
            : $referenceDate;

        $result = $this->generateForecastEntriesFromRecurrences->execute($company, $generationReferenceDate);

        return [
            'recurrence_id' => $recurrenceId,
            'entries_created' => $result['entries_created'],
        ];
    }

    /**
     * @param array{
     *     financial_category_id: int,
     *     type: string,
     *     description: string,
     *     amount_cents: int,
     *     competence_date?: string|null,
     *     reference_date: string,
     *     document_number?: string|null,
     *     vehicle_id?: int|null,
     *     driver_id?: int|null,
     *     trip_id?: int|null,
     *     payment_method?: string|null,
     *     installments?: int|null,
     *     launch_mode: string,
     *     status?: string
     * } $data
     * @return array{recurrence_id: int, entries_created: int}
     */
    private function createInstallments(
        Company $company,
        int $createdBy,
        array $data,
        int $recurrenceId,
        int $installments,
        string $referenceDate,
    ): array {
        $parts = Apportionment::equally((int) $data['amount_cents'], $installments);
        $referenceStart = CarbonImmutable::parse($referenceDate)->startOfDay();
        $competenceDate = CarbonImmutable::parse((string) $data['competence_date'])->toDateString();

        $this->tenant->runFor($company, function () use ($company, $createdBy, $data, $recurrenceId, $parts, $referenceStart, $competenceDate, $installments): void {
            DB::transaction(function () use ($company, $createdBy, $data, $recurrenceId, $parts, $referenceStart, $competenceDate, $installments): void {
                foreach ($parts as $index => $partAmountCents) {
                    $reference = $referenceStart->addMonthsNoOverflow($index)->toDateString();

                    FinancialEntry::query()->create([
                        'company_id' => $company->getKey(),
                        'bank_account_id' => null,
                        'financial_category_id' => $data['financial_category_id'],
                        'vehicle_id' => $data['vehicle_id'] ?? null,
                        'driver_id' => $data['driver_id'] ?? null,
                        'trip_id' => $data['trip_id'] ?? null,
                        'type' => $data['type'],
                        'description' => $data['description'],
                        'document_number' => $data['document_number'] ?? null,
                        'competence_date' => $competenceDate,
                        'reference_date' => $reference,
                        'due_date' => $reference,
                        'paid_at' => null,
                        'amount_cents' => $partAmountCents,
                        'settlement_discount_cents' => 0,
                        'settlement_interest_cents' => 0,
                        'status' => FinancialEntryStatus::Forecast->value,
                        'payment_method' => $data['payment_method'] ?? null,
                        'sourceable_type' => null,
                        'sourceable_id' => null,
                        'transfer_pair_id' => null,
                        'recurrence_id' => $recurrenceId,
                        'installment_number' => $index + 1,
                        'installment_total' => $installments,
                        'attachment_path' => null,
                        'reconciled_at' => null,
                        'created_by' => $createdBy,
                    ]);
                }

                Recurrence::query()->whereKey($recurrenceId)->update([
                    'installments_generated' => $installments,
                ]);
            });
        });

        return [
            'recurrence_id' => $recurrenceId,
            'entries_created' => $installments,
        ];
    }
}
