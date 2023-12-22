<?php

namespace App\Domain\Strava\Challenge\ReadModel;

use App\Domain\Strava\Challenge\Challenge;
use App\Domain\Strava\Challenge\ChallengeCollection;
use App\Domain\Strava\Challenge\ChallengeId;

interface ChallengeDetailsRepository
{
    public function findAll(): ChallengeCollection;

    public function find(ChallengeId $challengeId): Challenge;
}
