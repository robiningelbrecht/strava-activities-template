<?php

namespace App\Domain\Strava\Activity\ReadModel;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;

interface ActivityDetailsRepository
{
    public function find(int $id): Activity;

    public function findAll(int $limit = null): ActivityCollection;

    /**
     * @return int[]
     */
    public function findActivityIds(): array;

    /**
     * @return string[]
     */
    public function findUniqueGearIds(): array;
}
