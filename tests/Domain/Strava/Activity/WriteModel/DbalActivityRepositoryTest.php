<?php

namespace App\Tests\Domain\Strava\Activity\WriteModel;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\WriteModel\ActivityRepository;
use App\Domain\Strava\Activity\WriteModel\DbalActivityRepository;
use App\Domain\Strava\Gear\GearId;
use App\Infrastructure\Eventing\EventBus;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use App\Tests\DatabaseTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;

class DbalActivityRepositoryTest extends DatabaseTestCase
{
    use MatchesSnapshots;

    private ActivityRepository $activityRepository;
    private MockObject $eventBus;

    public function testItShouldSaveAndFind(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();

        $this->activityRepository->add($activity);

        $this->assertMatchesJsonSnapshot(
            $this->getConnectionFactory()
                ->getForYear(Year::fromDate($activity->getStartDate()))
                ->executeQuery('SELECT * FROM Activity')->fetchAllAssociative()
        );
    }

    public function testItShouldThrowOnDuplicateActivity(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();

        $this->expectException(UniqueConstraintViolationException::class);

        $this->activityRepository->add($activity);
        $this->activityRepository->add($activity);
    }

    public function testUpdate(): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->withGearId(GearId::fromUnprefixed('1'))
            ->build();
        $this->activityRepository->add($activity);

        $this->assertEquals(
            1,
            $activity->getKudoCount()
        );

        $activity->updateKudoCount(3);
        $activity->updateGearId(GearId::fromUnprefixed('10'));
        $this->activityRepository->update($activity);

        $this->assertMatchesJsonSnapshot(
            $this->getConnectionFactory()
                ->getForYear(Year::fromDate($activity->getStartDate()))
                ->executeQuery('SELECT * FROM Activity')->fetchAllAssociative()
        );
    }

    public function testDelete(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();
        $this->activityRepository->add($activity);

        $this->assertEquals(
            1,
            $this->getConnectionFactory()
                ->getForYear(Year::fromDate($activity->getStartDate()))
                ->executeQuery('SELECT COUNT(*) FROM Activity')->fetchOne()
        );

        $this->activityRepository->delete($activity);
        $this->assertEquals(
            0,
            $this->getConnectionFactory()
                ->getForYear(Year::fromDate($activity->getStartDate()))
                ->executeQuery('SELECT COUNT(*) FROM Activity')->fetchOne()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBus::class);

        $this->activityRepository = new DbalActivityRepository(
            $this->getConnectionFactory(),
            $this->eventBus
        );
    }
}
