<?php

namespace App\Domain\Strava\Activity\Stream;

interface ActivityPowerRepository
{
    public const TIME_INTERVAL_IN_SECONDS = [5, 10, 30, 60, 300, 480, 1200, 3600];

    /**
     * @return array<mixed>
     */
    public function findBestForActivity(int $activityId): array;

    /**
     * @return array<mixed>
     */
    public function findBest(): array;

    /**
     * @return array<int, int>
     */
    public function findTimeInSecondsPerWattageForActivity(int $activityId): array;
}
