<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\ActivityCollection;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\DbalActivityRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\DatabaseTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class DbalActivityRepositoryTest extends DatabaseTestCase
{
    private ActivityRepository $activityRepository;

    public function testItShouldSaveAndFind(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();

        $this->activityRepository->add($activity);

        $this->assertEquals(
            $activity,
            $this->activityRepository->find($activity->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->activityRepository->find(1);
    }

    public function testItShouldThrowOnDuplicateActivity(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();

        $this->expectException(UniqueConstraintViolationException::class);

        $this->activityRepository->add($activity);
        $this->activityRepository->add($activity);
    }

    public function testFindAll(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(1)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityRepository->add($activityOne);
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(2)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->activityRepository->add($activityTwo);
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(3)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityRepository->add($activityThree);

        $this->assertEquals(
            ActivityCollection::fromArray([$activityOne, $activityTwo, $activityThree]),
            $this->activityRepository->findAll()
        );
    }

    public function testFindActivityIds(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(1)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityRepository->add($activityOne);
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(2)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->activityRepository->add($activityTwo);
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(3)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityRepository->add($activityThree);

        $this->assertEquals(
            [1, 2, 3],
            $this->activityRepository->findActivityIds()
        );
    }

    public function testFindUniqueGearIds(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(1)
            ->withGearId(1)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityRepository->add($activityOne);
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(2)
            ->withGearId(2)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->activityRepository->add($activityTwo);
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(3)
            ->withGearId(1)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityRepository->add($activityThree);

        $this->assertEquals(
            [1, 2],
            $this->activityRepository->findUniqueGearIds()
        );
    }

    public function testUpdate(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withActivityId(1)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->withGearId('1')
            ->build();
        $this->activityRepository->add($activity);

        $this->assertEquals(
            1,
            $activity->getKudoCount()
        );

        $activity->updateKudoCount(3);
        $activity->updateGearId('10');
        $this->activityRepository->update($activity);

        $this->assertEquals(
            3,
            $this->activityRepository->find(1)->getKudoCount()
        );

        $this->assertEquals(
            '10',
            $this->activityRepository->find(1)->getGearId()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityRepository = new DbalActivityRepository(
            $this->getConnection()
        );
    }
}
