<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\ReadModel;

use App\Domain\Strava\Segment\Segment;
use App\Domain\Strava\Segment\SegmentCollection;

interface SegmentDetailsRepository
{
    public function find(int $id): Segment;

    public function findAll(): SegmentCollection;
}
