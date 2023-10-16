<?php

namespace App\Infrastructure\ValueObject\Geography;

abstract readonly class FloatLiteral
{
    final private function __construct(private float $float)
    {
        $this->guardValid($this->float);
    }

    protected function guardValid(float $float): void
    {
    }

    public function toFloat(): float
    {
        return $this->float;
    }

    public function equals(FloatLiteral $that): bool
    {
        return $this->float === $that->float;
    }

    public static function fromString(string $string): static
    {
        if (!\is_numeric($string)) {
            throw new \InvalidArgumentException(\sprintf('Invalid %s: %s', static::class, $string));
        }

        return new static((float) \trim($string));
    }

    public static function fromOptionalString(string $string = null): ?static
    {
        if (is_null($string)) {
            return null;
        }

        return static::fromString($string);
    }
}
