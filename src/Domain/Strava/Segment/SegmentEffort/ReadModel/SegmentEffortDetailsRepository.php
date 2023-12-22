<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort\ReadModel;

use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortCollection;

interface SegmentEffortDetailsRepository
{
    public function find(int $id): SegmentEffort;

    public function findBySegmentId(int $segmentId): SegmentEffortCollection;

    public function findByActivityId(int $activityId): SegmentEffortCollection;
}
