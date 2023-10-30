<?php

namespace App\Domain\Strava\Challenge;

interface ChallengeRepository
{
    public function findAll(): ChallengeCollection;

    public function find(string $id): Challenge;

    public function add(Challenge $challenge): void;
}
