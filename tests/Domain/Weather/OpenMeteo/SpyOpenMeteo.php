<?php

declare(strict_types=1);

namespace App\Tests\Domain\Weather\OpenMeteo;

use App\Domain\Weather\OpenMeteo\OpenMeteo;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

class SpyOpenMeteo implements OpenMeteo
{
    public function getWeatherStats(Latitude $latitude, Longitude $longitude, SerializableDateTime $date): array
    {
        return [];
    }
}
