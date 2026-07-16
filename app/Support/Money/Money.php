<?php

declare(strict_types=1);

namespace App\Support\Money;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(
        private int $cents,
        private string $currency = 'BRL'
    ) {
        if (trim($this->currency) === '') {
            throw new InvalidArgumentException('Currency is required.');
        }
    }

    public static function fromCents(int $cents, string $currency = 'BRL'): self
    {
        return new self($cents, $currency);
    }

    public static function zero(string $currency = 'BRL'): self
    {
        return new self(0, $currency);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->cents - $other->cents, $this->currency);
    }

    /**
     * @return array<int, self>
     */
    public function apportionEqually(int $parts): array
    {
        return array_map(
            fn (int $cents): self => new self($cents, $this->currency),
            Apportionment::equally($this->cents, $parts)
        );
    }

    /**
     * @param  array<int, int>  $weights
     * @return array<int, self>
     */
    public function apportionByWeights(array $weights): array
    {
        return array_map(
            fn (int $cents): self => new self($cents, $this->currency),
            Apportionment::distribute($this->cents, $weights)
        );
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents
            && $this->currency === $other->currency;
    }

    private function ensureSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Money operations require the same currency.');
        }
    }
}
