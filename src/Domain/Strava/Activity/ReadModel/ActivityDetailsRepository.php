<?php

namespace App\Domain\Strava\Activity\ReadModel;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIdCollection;
use App\Domain\Strava\Gear\GearIdCollection;

interface ActivityDetailsRepository
{
    public function find(ActivityId $activityId): Activity;

    public function findAll(int $limit = null): ActivityCollection;

    public function findActivityIds(): ActivityIdCollection;

    public function findUniqueGearIds(): GearIdCollection;

    public function findMostRiddenState(): ?string;
}
