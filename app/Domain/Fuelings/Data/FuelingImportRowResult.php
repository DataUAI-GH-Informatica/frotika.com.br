<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Data;

use App\Domain\Fuelings\Enums\FuelingImportItemStatus;

/**
 * Desfecho de uma linha da planilha. Vira um item do json `results` do lote e
 * uma linha da tela de resultado.
 */
final readonly class FuelingImportRowResult
{
    public function __construct(
        public int $number,
        public FuelingImportItemStatus $status,
        public ?string $message = null,
        public ?int $fuelingId = null,
        public ?string $plate = null,
        public ?string $code = null,
    ) {}

    public static function imported(int $number, int $fuelingId, string $plate, ?string $code): self
    {
        return new self($number, FuelingImportItemStatus::Imported, null, $fuelingId, $plate, $code);
    }

    public static function ignored(int $number, string $message, ?int $fuelingId, string $plate, ?string $code): self
    {
        return new self($number, FuelingImportItemStatus::Ignored, $message, $fuelingId, $plate, $code);
    }

    public static function failed(int $number, string $message, ?string $plate = null, ?string $code = null): self
    {
        return new self($number, FuelingImportItemStatus::Failed, $message, null, $plate, $code);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'row' => $this->number,
            'status' => $this->status->value,
            'message' => $this->message,
            'fueling_id' => $this->fuelingId,
            'plate' => $this->plate,
            'code' => $this->code,
        ];
    }
}
