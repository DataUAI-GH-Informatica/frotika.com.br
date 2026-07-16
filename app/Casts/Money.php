<?php

declare(strict_types=1);

namespace App\Casts;

use App\Support\Money\Money as MoneyValue;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class Money implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?MoneyValue
    {
        if ($value === null) {
            return null;
        }

        return MoneyValue::fromCents((int) $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof MoneyValue) {
            return $value->cents();
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw new InvalidArgumentException('Money cast expects cents as int/string or App\\Support\\Money\\Money instance.');
    }
}
