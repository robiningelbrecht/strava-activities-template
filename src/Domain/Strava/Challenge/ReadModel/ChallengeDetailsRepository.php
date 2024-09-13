<?php

namespace App\Domain\Strava\Challenge\ReadModel;

use App\Domain\Strava\Challenge\Challenge;
use App\Domain\Strava\Challenge\ChallengeId;
use App\Domain\Strava\Challenge\Challenges;

interface ChallengeDetailsRepository
{
    public function findAll(): Challenges;

    public function find(ChallengeId $challengeId): Challenge;
}
