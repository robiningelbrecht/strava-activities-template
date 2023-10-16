<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\StravaActivityRepository;
use App\Tests\DatabaseTestCase;

class StravaActivityRepositoryTest extends DatabaseTestCase
{
    private StravaActivityRepository $stravaActivityRepository;

    public function testItShouldSaveAndFind(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();

        $this->stravaActivityRepository->add($activity);

        $this->assertEquals(
            $activity,
            $this->stravaActivityRepository->find($activity->getId())
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->stravaActivityRepository = new StravaActivityRepository(
            $this->getConnection()
        );
    }
}
