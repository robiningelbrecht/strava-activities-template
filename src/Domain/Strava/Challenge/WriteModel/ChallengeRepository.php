<?php

namespace App\Domain\Strava\Challenge\WriteModel;

use App\Domain\Strava\Challenge\Challenge;

interface ChallengeRepository
{
    public function add(Challenge $challenge): void;
}
