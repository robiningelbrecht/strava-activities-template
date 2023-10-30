<?php

namespace App\Domain\Strava\Activity;

interface ActivityRepository
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

    public function add(Activity $activity): void;

    public function update(Activity $activity): void;
}
