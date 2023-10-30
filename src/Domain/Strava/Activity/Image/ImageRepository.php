<?php

namespace App\Domain\Strava\Activity\Image;

interface ImageRepository
{
    /**
     * @return \App\Domain\Strava\Activity\Image\Image[]
     */
    public function findAll(): array;
}
