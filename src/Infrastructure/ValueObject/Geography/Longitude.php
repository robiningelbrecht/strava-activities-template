<?php

namespace App\Infrastructure\ValueObject\Geography;

final readonly class Longitude extends FloatLiteral
{
    protected function guardValid(float $float): void
    {
        if (\abs($float) > 180) {
            throw new \InvalidArgumentException('Invalid longitude value: '.$float);
        }
    }
}
