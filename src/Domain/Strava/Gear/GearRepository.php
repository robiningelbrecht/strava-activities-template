<?php

namespace App\Domain\Strava\Gear;

interface GearRepository
{
    public function findAll(): GearCollection;

    public function find(string $id): Gear;

    public function add(Gear $gear): void;

    public function update(Gear $gear): void;
}
