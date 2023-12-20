<?php

namespace App\Domain\Strava\Gear\ReadModel;

use App\Domain\Strava\Gear\Gear;
use App\Domain\Strava\Gear\GearCollection;

interface GearDetailsRepository
{
    public function findAll(): GearCollection;

    public function find(string $id): Gear;
}
