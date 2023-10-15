<?php

namespace App\Infrastructure\ValueObject\Geography;

final readonly class Longitude extends FloatLiteral
{
    public function __construct(float $longitude)
    {
        $this->guardValid($longitude);
        parent::__construct($longitude);
    }

    private function guardValid(float $longitude): void
    {
        if (\abs($longitude) > 180) {
            throw new \InvalidArgumentException('Invalid longitude value: '.$longitude);
        }
    }
}
