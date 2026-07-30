<?php

declare(strict_types=1);

namespace App\Domain\Attachments\Models;

use App\Models\User;
use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $size_bytes
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime
 */
final class Attachment extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function extension(): string
    {
        return mb_strtoupper(pathinfo($this->original_name, PATHINFO_EXTENSION));
    }
}
