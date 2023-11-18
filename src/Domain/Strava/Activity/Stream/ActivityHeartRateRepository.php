<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Athlete\HeartRateZone;

interface ActivityHeartRateRepository
{
    public function findTotalTimeInSecondsInHeartRateZone(HeartRateZone $heartRateZone): int;

    /**
     * @return array<int, int>
     */
    public function findTimeInSecondsPerHeartRateForActivity(int $activityId): array;
}
