<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort\WriteModel;

use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;

interface SegmentEffortRepository
{
    public function add(SegmentEffort $segmentEffort): void;

    public function update(SegmentEffort $segmentEffort): void;

    public function delete(SegmentEffort $segmentEffort): void;
}
