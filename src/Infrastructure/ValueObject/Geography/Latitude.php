<?php

namespace App\Infrastructure\ValueObject\Geography;

final readonly class Latitude extends FloatLiteral
{
    public function __construct(float $latitude)
    {
        $this->guardValid($latitude);
        parent::__construct($latitude);
    }

    private function guardValid(float $latitude): void
    {
        if (\abs($latitude) > 90) {
            throw new \InvalidArgumentException('Invalid latitude value: '.$latitude);
        }
    }
}
