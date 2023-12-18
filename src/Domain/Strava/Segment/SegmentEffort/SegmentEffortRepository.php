<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort;

interface SegmentEffortRepository
{
    public function find(int $id): SegmentEffort;

    public function findBySegmentId(int $segmentId): SegmentEffortCollection;

    public function add(SegmentEffort $segmentEffort): void;

    public function update(SegmentEffort $segmentEffort): void;
}
