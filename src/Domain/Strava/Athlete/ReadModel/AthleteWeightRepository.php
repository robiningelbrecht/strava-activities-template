<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\ReadModel;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Weight;

interface AthleteWeightRepository
{
    public function find(SerializableDateTime $dateTime): ?Weight;
}
