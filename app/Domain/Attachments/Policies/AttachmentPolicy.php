<?php

declare(strict_types=1);

namespace App\Domain\Attachments\Policies;

use App\Domain\Attachments\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Anexo não tem permissão própria: quem enxerga o abastecimento enxerga o
 * cupom dele, e quem pode mexer no CT-e pode remover o documento anexado. Toda
 * decisão é delegada à policy do dono.
 */
final class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        $owner = $this->ownerOf($attachment);

        return $owner !== null && Gate::forUser($user)->allows('view', $owner);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        $owner = $this->ownerOf($attachment);

        return $owner !== null && Gate::forUser($user)->allows('attach', $owner);
    }

    /**
     * O dono é resolvido com o global scope ativo: anexo de outra empresa não
     * resolve, e sem dono não há permissão.
     */
    private function ownerOf(Attachment $attachment): ?Model
    {
        $owner = $attachment->attachable;

        return $owner instanceof Model ? $owner : null;
    }
}
