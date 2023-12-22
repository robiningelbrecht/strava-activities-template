<?php

namespace App\Domain\Strava\Activity\WriteModel;

use App\Domain\Strava\Activity\Activity;

interface ActivityRepository
{
    public function add(Activity $activity): void;

    public function update(Activity $activity): void;

    public function delete(Activity $activity): void;
}
