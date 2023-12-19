<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort;

use App\Domain\Strava\Activity\Activity;
use App\Infrastructure\Time\TimeFormatter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class SegmentEffort
{
    use TimeFormatter;

    private ?Activity $activity = null;

    /**
     * @param array<mixed> $data
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly int $segmentEffortId,
        #[ORM\Column(type: 'string')]
        private readonly int $segmentId,
        #[ORM\Column(type: 'string')]
        private readonly int $activityId,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $startDateTime,
        #[ORM\Column(type: 'json')]
        private readonly array $data,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function create(
        int $segmentEffortId,
        int $segmentId,
        int $activityId,
        SerializableDateTime $startDateTime,
        array $data,
    ): self {
        return new self(
            segmentEffortId: $segmentEffortId,
            segmentId: $segmentId,
            activityId: $activityId,
            startDateTime: $startDateTime,
            data: $data,
        );
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromState(
        int $segmentEffortId,
        int $segmentId,
        int $activityId,
        SerializableDateTime $startDateTime,
        array $data,
    ): self {
        return new self(
            segmentEffortId: $segmentEffortId,
            segmentId: $segmentId,
            activityId: $activityId,
            startDateTime: $startDateTime,
            data: $data,
        );
    }

    public function getId(): int
    {
        return $this->segmentEffortId;
    }

    public function getSegmentId(): int
    {
        return $this->segmentId;
    }

    public function getActivityId(): int
    {
        return $this->activityId;
    }

    public function getStartDateTime(): SerializableDateTime
    {
        return $this->startDateTime;
    }

    public function getElapsedTimeInSeconds(): float
    {
        return (float) $this->data['elapsed_time'];
    }

    public function getElapsedTimeFormatted(): string
    {
        return $this->formatDurationForHumans((int) round($this->getElapsedTimeInSeconds()));
    }

    public function getAverageWatts(): ?float
    {
        if (isset($this->data['average_watts'])) {
            return (float) $this->data['average_watts'];
        }

        return null;
    }

    public function getAverageSpeedInKmPerH(): float
    {
        $averageSpeed = $this->data['distance'] / $this->getElapsedTimeInSeconds();

        return round($averageSpeed * 3.6, 1);
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function enrichWithActivity(Activity $activity): void
    {
        $this->activity = $activity;
    }
}
