<?php

declare(strict_types=1);

namespace App\Tests\Domain\Nominatim;

use App\Domain\Nominatim\Location;
use App\Domain\Nominatim\Nominatim;
use App\Infrastructure\ValueObject\Geography\Coordinate;

class SpyNominatim implements Nominatim
{
    public function reverseGeocode(Coordinate $coordinate): Location
    {
        return Location::fromState([
            'country_code' => 'be',
            'state' => 'West Vlaanderen',
        ]);
    }
}
