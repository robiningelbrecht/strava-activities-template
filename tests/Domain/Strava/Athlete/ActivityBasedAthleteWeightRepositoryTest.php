<?php

namespace App\Tests\Domain\Strava\Athlete;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Athlete\ActivityBasedAthleteWeightRepository;
use App\Domain\Strava\Athlete\AthleteWeightRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Weight;
use App\Tests\DatabaseTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;

class ActivityBasedAthleteWeightRepositoryTest extends DatabaseTestCase
{
    private AthleteWeightRepository $athleteWeightRepository;

    public function testFind(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(2)
                ->withStartDateTime(SerializableDateTime::fromString('2023-02-01'))
                ->withData([
                    'athlete_weight' => 68,
                ])
                ->build()
        );

        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(1)
                ->withStartDateTime(SerializableDateTime::fromString('2023-04-01'))
                ->withData([
                    'athlete_weight' => 69,
                ])
                ->build()
        );

        $this->assertEquals(
            Weight::fromKilograms(68),
            $this->athleteWeightRepository->find(SerializableDateTime::fromString('2023-02-01')),
        );
        $this->assertEquals(
            Weight::fromKilograms(68),
            $this->athleteWeightRepository->find(SerializableDateTime::fromString('2023-03-01')),
        );
        $this->assertEquals(
            Weight::fromKilograms(69),
            $this->athleteWeightRepository->find(SerializableDateTime::fromString('2023-04-01')),
        );
        $this->assertEquals(
            Weight::fromKilograms(69),
            $this->athleteWeightRepository->find(SerializableDateTime::fromString('2023-04-02')),
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(2)
                ->withStartDateTime(SerializableDateTime::fromString('2023-02-01'))
                ->withData([
                    'athlete_weight' => 68,
                ])
                ->build()
        );

        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(1)
                ->withStartDateTime(SerializableDateTime::fromString('2023-04-01'))
                ->withData([
                    'athlete_weight' => 69,
                ])
                ->build()
        );

        $this->expectException(EntityNotFound::class);

        $this->athleteWeightRepository->find(SerializableDateTime::fromString('2023-01-01'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->athleteWeightRepository = new ActivityBasedAthleteWeightRepository(
            $this->getConnection()
        );
    }
}
