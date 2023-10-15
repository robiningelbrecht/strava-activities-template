<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Infrastructure\ValueObject\Weight;

final readonly class PowerStream implements ActivityStream
{
    private function __construct(
        private ActivityStream $activityStream
    ) {
    }

    public static function fromStream(ActivityStream $stream): self
    {
        return new self($stream);
    }

    public function getName(): string
    {
        return $this->activityStream->getName();
    }

    public function getActivityId(): string
    {
        return $this->activityStream->getActivityId();
    }

    public function getType(): StreamType
    {
        return $this->activityStream->getType();
    }

    public function getData(): array
    {
        return $this->activityStream->getData();
    }

    public function getBestAverageForTimeInterval(int $timeIntervalInSeconds): ?int
    {
        return $this->activityStream->getBestAverageForTimeInterval($timeIntervalInSeconds);
    }

    public function getBestRelativeAverageForTimeInterval(int $timeIntervalInSeconds, Weight $athleteWeight): ?float
    {
        if ($averagePower = $this->activityStream->getBestAverageForTimeInterval($timeIntervalInSeconds)) {
            return round($averagePower / $athleteWeight->getFloat(), 2);
        }

        return null;
    }

    public function jsonSerialize(): array
    {
        return $this->activityStream->jsonSerialize();
    }
}
