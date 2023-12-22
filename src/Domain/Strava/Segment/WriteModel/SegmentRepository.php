<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\WriteModel;

use App\Domain\Strava\Segment\Segment;

interface SegmentRepository
{
    public function add(Segment $segment): void;
}
