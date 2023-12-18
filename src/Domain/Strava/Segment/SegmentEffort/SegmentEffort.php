<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final readonly class SegmentEffort
{
    /**
     * @param array<mixed> $data
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private int $segmentEffortId,
        #[ORM\Column(type: 'string')]
        private int $segmentId,
        #[ORM\Column(type: 'string')]
        private int $activityId,
        #[ORM\Column(type: 'datetime_immutable')]
        private SerializableDateTime $startDateTime,
        #[ORM\Column(type: 'json')]
        private array $data,
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

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
