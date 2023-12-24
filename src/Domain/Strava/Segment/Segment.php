<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment;

use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Infrastructure\ValueObject\String\Name;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class Segment
{
    private ?SegmentEffort $bestEffort = null;

    /**
     * @param array<mixed> $data
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly SegmentId $segmentId,
        #[ORM\Column(type: 'string', nullable: true)]
        private readonly Name $name,
        #[ORM\Column(type: 'json')]
        private readonly array $data,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function create(
        SegmentId $segmentId,
        Name $name,
        array $data,
    ): self {
        return new self(
            segmentId: $segmentId,
            name: $name,
            data: $data,
        );
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromState(
        SegmentId $segmentId,
        Name $name,
        array $data,
    ): self {
        return new self(
            segmentId: $segmentId,
            name: $name,
            data: $data,
        );
    }

    public function getId(): SegmentId
    {
        return $this->segmentId;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getDistanceInKilometer(): float
    {
        return $this->data['distance'] / 1000;
    }

    public function getMaxGradient(): float
    {
        return $this->data['maximum_grade'];
    }

    public function getActivityType(): ActivityType
    {
        return ActivityType::from($this->data['activity_type']);
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string[]
     */
    public function getSearchables(): array
    {
        return [(string) $this->getName()];
    }

    public function getBestEffort(): ?SegmentEffort
    {
        return $this->bestEffort;
    }

    public function enrichWithBestEffort(SegmentEffort $segmentEffort): void
    {
        $this->bestEffort = $segmentEffort;
    }
}
