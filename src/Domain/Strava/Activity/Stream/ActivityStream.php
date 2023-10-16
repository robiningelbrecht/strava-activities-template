<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface ActivityStream
{
    public function getName(): string;

    public function getActivityId(): int;

    public function getStreamType(): StreamType;

    /**
     * @return array<mixed>
     */
    public function getData(): array;

    public function getCreatedOn(): SerializableDateTime;

    public function getBestAverageForTimeInterval(int $timeIntervalInSeconds): ?int;
}
