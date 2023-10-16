<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\StravaActivityRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\DatabaseTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

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

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->stravaActivityRepository->find(1);
    }

    public function testItShouldThrowOnDuplicateActivity(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();

        $this->expectException(UniqueConstraintViolationException::class);

        $this->stravaActivityRepository->add($activity);
        $this->stravaActivityRepository->add($activity);
    }

    public function testFindAll(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(1)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->stravaActivityRepository->add($activityOne);
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(2)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->stravaActivityRepository->add($activityTwo);
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(3)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->stravaActivityRepository->add($activityThree);

        $this->assertEquals(
            [$activityOne, $activityTwo, $activityThree],
            $this->stravaActivityRepository->findAll()
        );
    }

    public function testFindActivityIds(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(1)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->stravaActivityRepository->add($activityOne);
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(2)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->stravaActivityRepository->add($activityTwo);
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(3)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->stravaActivityRepository->add($activityThree);

        $this->assertEquals(
            [1, 2, 3],
            $this->stravaActivityRepository->findActivityIds()
        );
    }

    public function testFindUniqueGearIds(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(1)
            ->withGearId(1)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->stravaActivityRepository->add($activityOne);
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(2)
            ->withGearId(2)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->stravaActivityRepository->add($activityTwo);
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(3)
            ->withGearId(1)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->stravaActivityRepository->add($activityThree);

        $this->assertEquals(
            [1, 2],
            $this->stravaActivityRepository->findUniqueGearIds()
        );
    }

    public function testUpdate(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withActivityId(1)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->stravaActivityRepository->add($activity);

        $this->assertEquals(
            1,
            $activity->getKudoCount()
        );

        $activity->updateKudoCount(3);
        $this->stravaActivityRepository->update($activity);

        $this->assertEquals(
            3,
            $this->stravaActivityRepository->find(1)->getKudoCount()
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
