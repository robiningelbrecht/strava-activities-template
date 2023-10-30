<?php

namespace App\Tests\Domain\Strava\Athlete;

use App\Domain\Strava\Athlete\ActivityBasedAthleteWeightRepository;
use App\Domain\Strava\Athlete\AthleteWeightRepository;
use App\Tests\DatabaseTestCase;

class ActivityBasedAthleteWeightRepositoryTest extends DatabaseTestCase
{
    private AthleteWeightRepository $athleteWeightRepository;

    public function testFind(): void
    {
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->athleteWeightRepository = new ActivityBasedAthleteWeightRepository(
            $this->getConnection()
        );
    }
}
