<?php

namespace App\Infrastructure\ValueObject\Geography;

final readonly class Latitude extends FloatLiteral
{
    protected function guardValid(float $float): void
    {
        if (\abs($float) > 90) {
            throw new \InvalidArgumentException('Invalid latitude value: '.$float);
        }
    }
}
