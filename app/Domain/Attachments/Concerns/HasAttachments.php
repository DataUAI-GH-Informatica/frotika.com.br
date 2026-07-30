<?php

declare(strict_types=1);

namespace App\Domain\Attachments\Concerns;

use App\Domain\Attachments\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** @mixin Model */
trait HasAttachments
{
    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest('id');
    }
}
