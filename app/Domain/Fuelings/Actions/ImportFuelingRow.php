<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Actions;

use App\Domain\Fleet\Models\Driver;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fuelings\Data\FuelingData;
use App\Domain\Fuelings\Data\FuelingImportRowData;
use App\Domain\Fuelings\Data\FuelingImportRowResult;
use App\Domain\Fuelings\Import\Exceptions\FuelingImportRowException;
use App\Domain\Fuelings\Import\FuelingImportRow;
use App\Domain\Fuelings\Import\FuelingRowParser;
use App\Domain\Fuelings\Models\Fueling;
use App\Domain\Partners\Models\BusinessPartner;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

/**
 * Importa UMA linha da planilha. Toda a persistência passa pelo CreateFueling —
 * é o que garante que o abastecimento importado siga exatamente as mesmas regras
 * do lançado pela tela: guarda de odômetro, recálculo de consumo, observer e
 * lançamento financeiro (regras 7 e 8).
 *
 * Nunca duplica: com `codigo_abastecimento` preenchido, o código é a chave;
 * sem código, a assinatura placa + data/hora + litros + valor total. Duplicidade
 * é linha ignorada, não falha — reimportar a mesma planilha é operação normal.
 */
final class ImportFuelingRow
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly FuelingRowParser $parser,
        private readonly ResolveFuelingStationForImport $stations,
        private readonly CreateFueling $createFueling,
    ) {}

    /**
     * @throws FuelingImportRowException
     */
    public function execute(User $actor, Company $company, FuelingImportRow $row): FuelingImportRowResult
    {
        $data = $this->parser->parse($row);

        return $this->tenant->runFor($company, function () use ($actor, $company, $data): FuelingImportRowResult {
            $vehicle = Vehicle::query()->where('plate', $data->plate)->first();

            if (! $vehicle instanceof Vehicle) {
                throw FuelingImportRowException::vehicleNotFound($data->plate);
            }

            $duplicate = $this->findDuplicate($data, (int) $vehicle->getKey());

            if ($duplicate instanceof Fueling) {
                return FuelingImportRowResult::ignored(
                    $data->number,
                    $this->duplicateMessage($data, $duplicate),
                    (int) $duplicate->getKey(),
                    $data->plate,
                    $data->importCode,
                );
            }

            // Motorista antes do posto: se o CPF não existe, a linha falha sem
            // ter deixado um parceiro cadastrado por uma importação que não foi.
            $driverId = $this->driverId($data);
            $station = $this->stations->execute($company, $data->station);

            $attributes = $data->toFuelingAttributes(
                (int) $vehicle->getKey(),
                $driverId,
                $station instanceof BusinessPartner ? (int) $station->getKey() : null,
            );

            return $this->persist($actor, $company, $data, $attributes);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function persist(User $actor, Company $company, FuelingImportRowData $data, array $attributes): FuelingImportRowResult
    {
        try {
            $fueling = $this->createFueling->execute($actor, $company, FuelingData::fromArray($attributes));
        } catch (ValidationException $exception) {
            throw $this->translate($exception, $data);
        } catch (QueryException $exception) {
            // Corrida entre dois envios da mesma planilha: o índice único de
            // (company_id, import_code) barra o segundo. É duplicidade, não erro.
            if ($data->importCode !== null && $this->isUniqueViolation($exception)) {
                return FuelingImportRowResult::ignored(
                    $data->number,
                    sprintf('Código %s já importado nesta empresa.', $data->importCode),
                    null,
                    $data->plate,
                    $data->importCode,
                );
            }

            throw $exception;
        }

        return FuelingImportRowResult::imported(
            $data->number,
            (int) $fueling->getKey(),
            $data->plate,
            $data->importCode,
        );
    }

    private function driverId(FuelingImportRowData $data): ?int
    {
        if ($data->driverCpf === null) {
            return null;
        }

        $driver = Driver::query()->where('cpf', $data->driverCpf)->first();

        if (! $driver instanceof Driver) {
            throw FuelingImportRowException::driverNotFound($data->driverCpf);
        }

        return (int) $driver->getKey();
    }

    /**
     * Com código, a busca inclui o excluído: o índice único não distingue soft
     * delete, então recusar aqui é melhor que estourar violação no insert.
     */
    private function findDuplicate(FuelingImportRowData $data, int $vehicleId): ?Fueling
    {
        if ($data->importCode !== null) {
            return Fueling::query()
                ->withTrashed()
                ->where('import_code', $data->importCode)
                ->first();
        }

        return Fueling::query()
            ->where('vehicle_id', $vehicleId)
            ->where('fueled_at', $data->fueledAt)
            ->where('total_cents', $data->totalCents)
            ->get()
            ->first(fn (Fueling $fueling): bool => $this->sameLiters($fueling, $data->liters));
    }

    /**
     * Litros é decimal(10,3) no banco. Comparar em PHP, já arredondado na mesma
     * casa, evita depender de como cada banco compara decimal com parâmetro.
     */
    private function sameLiters(Fueling $fueling, float $liters): bool
    {
        return number_format((float) $fueling->getAttribute('liters'), 3, '.', '')
            === number_format($liters, 3, '.', '');
    }

    private function duplicateMessage(FuelingImportRowData $data, Fueling $duplicate): string
    {
        if ($data->importCode !== null) {
            return sprintf('Código %s já importado antes (abastecimento #%d).', $data->importCode, $duplicate->getKey());
        }

        return sprintf('Abastecimento idêntico já lançado (#%d): mesma placa, data, litros e valor.', $duplicate->getKey());
    }

    private function translate(ValidationException $exception, FuelingImportRowData $data): FuelingImportRowException
    {
        $errors = $exception->errors();

        if (isset($errors['odometer'])) {
            return FuelingImportRowException::odometerRollback($data->plate, $data->odometer);
        }

        $first = array_values($errors)[0][0] ?? $exception->getMessage();

        return FuelingImportRowException::rejected((string) $first);
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        return (string) $exception->getCode() === '23000';
    }
}
