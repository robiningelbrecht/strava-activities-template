<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface ActivityStream
{
    public function getName(): string;

    public function getActivityId(): ActivityId;

    public function getStreamType(): StreamType;

    /**
     * @return array<mixed>
     */
    public function getData(): array;

    public function getCreatedOn(): SerializableDateTime;

    public function calculateBestAverageForTimeInterval(int $timeIntervalInSeconds): ?int;

    /**
     * @return array<int, int>
     */
    public function getBestAverages(): array;

    /**
     * @param array<int, int> $averages
     */
    public function updateBestAverages(array $averages): void;
}
