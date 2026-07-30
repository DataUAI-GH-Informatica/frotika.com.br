<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Avisa quem disparou a importação que o lote terminou. Vai pelo canal privado
 * do usuário (ADR-007) porque quem espera o resultado é a pessoa que enviou a
 * planilha, não a empresa inteira.
 */
final class FuelingBulkImportCompleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public readonly int $userId,
        public readonly string $uuid,
        public readonly int $total,
        public readonly int $imported,
        public readonly int $ignored,
        public readonly int $failed,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'fueling-import.completed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'uuid' => $this->uuid,
            'total' => $this->total,
            'imported' => $this->imported,
            'ignored' => $this->ignored,
            'failed' => $this->failed,
            'url' => route('fuelings.import.result', ['batch' => $this->uuid]),
        ];
    }
}
