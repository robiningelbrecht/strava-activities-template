<?php

namespace App\Domain\Strava\Gear\ReadModel;

use App\Domain\Strava\Gear\Gear;
use App\Domain\Strava\Gear\GearCollection;
use App\Domain\Strava\Gear\GearId;

interface GearDetailsRepository
{
    public function findAll(): GearCollection;

    public function find(GearId $gearId): Gear;
}
