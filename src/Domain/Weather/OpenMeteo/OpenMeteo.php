<?php

declare(strict_types=1);

namespace App\Domain\Weather\OpenMeteo;

use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface OpenMeteo
{
    /**
     * @return array<mixed>
     */
    public function getWeatherStats(
        Latitude $latitude,
        Longitude $longitude,
        SerializableDateTime $date,
    ): array;
}
