<?php

namespace App\Domain\Strava\Activity\Stream;

interface ActivityStream extends \JsonSerializable
{
    public function getName(): string;

    public function getActivityId(): string;

    public function getType(): StreamType;

    public function getData(): array;

    public function getBestAverageForTimeInterval(int $timeIntervalInSeconds): ?int;
}
