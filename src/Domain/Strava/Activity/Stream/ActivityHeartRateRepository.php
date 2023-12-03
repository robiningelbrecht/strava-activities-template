<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Athlete\HeartRateZone;

interface ActivityHeartRateRepository
{
    public const TIME_INTERVAL_IN_SECONDS = [5, 10, 30, 60, 300, 480, 1200, 3600];

    public function findTotalTimeInSecondsInHeartRateZone(HeartRateZone $heartRateZone): int;

    /**
     * @return HeartRate[]
     */
    public function findHighest(): array;

    /**
     * @return array<int, int>
     */
    public function findTimeInSecondsPerHeartRateForActivity(int $activityId): array;
}
