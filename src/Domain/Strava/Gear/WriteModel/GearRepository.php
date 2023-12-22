<?php

namespace App\Domain\Strava\Gear\WriteModel;

use App\Domain\Strava\Gear\Gear;

interface GearRepository
{
    public function add(Gear $gear): void;

    public function update(Gear $gear): void;
}
