<?php

namespace App\Domain\Strava\Challenge;

interface ChallengeRepository
{
    public function findAll(): ChallengeCollection;

    public function find(ChallengeId $challengeId): Challenge;

    public function add(Challenge $challenge): void;
}
