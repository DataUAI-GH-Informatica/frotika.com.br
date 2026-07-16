<?php

declare(strict_types=1);

namespace App\Support\Tenancy\Exceptions;

use RuntimeException;

final class MissingTenantContextException extends RuntimeException
{
    public static function forModel(string $modelClass): self
    {
        return new self(sprintf('Tenant context is required to persist model [%s].', $modelClass));
    }
}
