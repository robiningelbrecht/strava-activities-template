<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Segment;

use App\Domain\Strava\Segment\Segment;
use App\Infrastructure\ValueObject\String\Name;

final class SegmentBuilder
{
    private int $segmentId;
    private Name $name;
    private readonly array $data;

    private function __construct()
    {
        $this->segmentId = 1;
        $this->name = Name::fromString('Segment');
        $this->data = [];
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): Segment
    {
        return Segment::fromState(
            segmentId: $this->segmentId,
            name: $this->name,
            data: $this->data,
        );
    }

    public function withId(int $id): self
    {
        $this->segmentId = $id;

        return $this;
    }

    public function withName(Name $name): self
    {
        $this->name = $name;

        return $this;
    }
}