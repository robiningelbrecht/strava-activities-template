<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface ActivityStream
{
    public function getName(): string;

    public function getActivityId(): string;

    public function getStreamType(): StreamType;

    public function getData(): array;

    public function getCreatedOn(): SerializableDateTime;

    public function getBestAverageForTimeInterval(int $timeIntervalInSeconds): ?int;
}
