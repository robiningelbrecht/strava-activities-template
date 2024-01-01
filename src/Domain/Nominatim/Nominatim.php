<?php

declare(strict_types=1);

namespace App\Domain\Nominatim;

use App\Infrastructure\ValueObject\Geography\Coordinate;

interface Nominatim
{
    public function reverseGeocode(Coordinate $coordinate): Address;
}
