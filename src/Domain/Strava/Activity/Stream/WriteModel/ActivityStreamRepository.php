<?php

namespace App\Domain\Strava\Activity\Stream\WriteModel;

use App\Domain\Strava\Activity\Stream\ActivityStream;

interface ActivityStreamRepository
{
    public function add(ActivityStream $stream): void;

    public function delete(ActivityStream $stream): void;
}
