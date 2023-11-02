<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Athlete\HeartRateZone;

interface ActivityHeartRateRepository
{
    public function findTimeInSecondsInHeartRateZoneForActivity(int $activityId, HeartRateZone $heartRateZone): int;

    public function findTotalTimeInSecondsInHeartRateZone(HeartRateZone $heartRateZone): int;
}
