<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\ReadModel;

use App\Domain\Strava\Segment\Segment;
use App\Domain\Strava\Segment\SegmentCollection;
use App\Domain\Strava\Segment\SegmentId;

interface SegmentDetailsRepository
{
    public function find(SegmentId $segmentId): Segment;

    public function findAll(): SegmentCollection;
}
