<?php

namespace App\Domain\Strava\Gear\ReadModel;

use App\Domain\Strava\Gear\Gear;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\Gears;

interface GearDetailsRepository
{
    public function findAll(): Gears;

    public function find(GearId $gearId): Gear;
}
