<?php

namespace App\Domain\Strava\Activity\ReadModel;

use App\Domain\Strava\Activity\Activities;
use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Domain\Strava\Gear\GearIdCollection;

interface ActivityDetailsRepository
{
    public function find(ActivityId $activityId): Activity;

    public function findAll(?int $limit = null): Activities;

    public function findActivityIds(): ActivityIds;

    public function findUniqueGearIds(): GearIdCollection;

    public function findMostRiddenState(): ?string;
}
