<?php

declare(strict_types=1);

namespace App\Notifications\Fleet;

use App\Domain\Tenancy\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ExpiringDocumentsNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function __construct(
        private readonly Company $company,
        private readonly array $items,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tradeName = (string) ($this->company->getAttribute('trade_name') ?: $this->company->getAttribute('legal_name'));
        $title = $this->title();

        return (new MailMessage)
            ->subject(sprintf('%s — %s', $title, $tradeName))
            ->view('emails.fleet.expiring-documents', [
                'user' => $notifiable,
                'company' => $this->company,
                'items' => $this->items,
                'title' => $title,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $tradeName = (string) ($this->company->getAttribute('trade_name') ?: $this->company->getAttribute('legal_name'));

        return [
            'title' => $this->title(),
            'message' => sprintf('%d documento(s) com vencimento próximo para %s.', count($this->items), $tradeName),
            'level' => $this->level(),
            'company_id' => $this->company->getKey(),
            'company_name' => $tradeName,
            'action_url' => route('vehicles.index'),
            'items' => $this->items,
        ];
    }

    private function title(): string
    {
        return $this->level() === 'danger'
            ? 'Documentos vencidos na frota'
            : 'Documentos vencendo na frota';
    }

    private function level(): string
    {
        foreach ($this->items as $item) {
            if (($item['alert'] ?? null) === 'expired') {
                return 'danger';
            }
        }

        return 'warning';
    }
}
