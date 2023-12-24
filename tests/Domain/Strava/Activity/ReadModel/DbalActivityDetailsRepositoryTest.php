<?php

namespace App\Tests\Domain\Strava\Activity\ReadModel;

use App\Domain\Strava\Activity\ActivityCollection;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIdCollection;
use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Domain\Strava\Activity\ReadModel\DbalActivityDetailsRepository;
use App\Domain\Strava\Activity\WriteModel\ActivityRepository;
use App\Domain\Strava\Activity\WriteModel\DbalActivityRepository;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearIdCollection;
use App\Infrastructure\Eventing\EventBus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\DatabaseTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;

class DbalActivityDetailsRepositoryTest extends DatabaseTestCase
{
    private ActivityDetailsRepository $activityDetailsRepository;
    private ActivityRepository $activityRepository;

    public function testFind(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();
        $this->activityRepository->add($activity);

        $this->assertEquals(
            $activity,
            $this->activityDetailsRepository->find($activity->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->activityDetailsRepository->find(ActivityId::fromUnprefixed(1));
    }

    public function testFindAll(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityRepository->add($activityOne);
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->activityRepository->add($activityTwo);
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(3))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityRepository->add($activityThree);

        $this->assertEquals(
            ActivityCollection::fromArray([$activityOne, $activityTwo, $activityThree]),
            $this->activityDetailsRepository->findAll()
        );
    }

    public function testFindActivityIds(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityRepository->add($activityOne);
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->activityRepository->add($activityTwo);
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(3))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityRepository->add($activityThree);

        $this->assertEquals(
            ActivityIdCollection::fromArray([
                ActivityId::fromUnprefixed(1),
                ActivityId::fromUnprefixed(2),
                ActivityId::fromUnprefixed(3),
            ]),
            $this->activityDetailsRepository->findActivityIds()
        );
    }

    public function testFindUniqueGearIds(): void
    {
        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withGearId(GearId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->activityRepository->add($activityOne);
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withGearId(GearId::fromUnprefixed(2))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->activityRepository->add($activityTwo);
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(3))
            ->withGearId(GearId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->activityRepository->add($activityThree);
        $this->activityRepository->add(ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(4))
            ->withoutGearId()
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build());

        $this->assertEquals(
            GearIdCollection::fromArray([GearId::fromUnprefixed(1), GearId::fromUnprefixed(2)]),
            $this->activityDetailsRepository->findUniqueGearIds()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityDetailsRepository = new DbalActivityDetailsRepository(
            $this->getConnectionFactory()
        );
        $this->activityRepository = new DbalActivityRepository(
            $this->getConnectionFactory(),
            $this->getContainer()->get(EventBus::class),
        );
    }
}
