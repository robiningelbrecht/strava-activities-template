<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort\ReadModel;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortCollection;
use App\Domain\Strava\Segment\SegmentId;

interface SegmentEffortDetailsRepository
{
    public function find(int $id): SegmentEffort;

    public function findBySegmentId(SegmentId $segmentId): SegmentEffortCollection;

    public function findByActivityId(ActivityId $activityId): SegmentEffortCollection;
}
