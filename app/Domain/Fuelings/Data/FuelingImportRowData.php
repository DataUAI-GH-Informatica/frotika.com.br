<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Data;

use App\Domain\Fuelings\Enums\FuelingPaymentMethod;
use App\Domain\Fuelings\Enums\FuelProduct;
use App\Domain\Fuelings\Enums\FuelTank;

/**
 * Uma linha da planilha já validada e normalizada: tipos certos, enums
 * resolvidos, documentos só com dígitos. Ainda não conhece os ids do banco —
 * placa, CPF e CNPJ viram vínculo na Action de importação.
 */
final readonly class FuelingImportRowData
{
    public function __construct(
        public int $number,
        public string $plate,
        public string $fueledAt,
        public int $odometer,
        public FuelProduct $product,
        public float $liters,
        public int $totalCents,
        public FuelingPaymentMethod $paymentMethod,
        public FuelTank $tank,
        public bool $fullTank,
        public FuelingStationImportData $station,
        public ?string $importCode = null,
        public ?float $pricePerLiter = null,
        public ?string $driverCpf = null,
        public ?string $invoiceNumber = null,
        public ?string $notes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toFuelingAttributes(?int $vehicleId, ?int $driverId, ?int $supplierId): array
    {
        return [
            'vehicle_id' => $vehicleId,
            'driver_id' => $driverId,
            'supplier_id' => $supplierId,
            'fueled_at' => $this->fueledAt,
            'odometer' => $this->odometer,
            'product' => $this->product->value,
            'liters' => $this->liters,
            'price_per_liter' => $this->pricePerLiter,
            'total_cents' => $this->totalCents,
            'full_tank' => $this->fullTank,
            'tank' => $this->tank->value,
            'payment_method' => $this->paymentMethod->value,
            'station_name' => $this->station->displayName(),
            'station_city' => $this->station->city,
            'station_state' => $this->station->state,
            'invoice_number' => $this->invoiceNumber,
            'notes' => $this->notes,
            'import_code' => $this->importCode,
        ];
    }
}
