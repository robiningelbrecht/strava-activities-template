<?php

namespace App\Tests\Domain\Strava\Activity\WriteModel;

use App\Domain\Strava\Activity\WriteModel\ActivityRepository;
use App\Domain\Strava\Activity\WriteModel\DbalActivityRepository;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use App\Tests\DatabaseTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Spatie\Snapshots\MatchesSnapshots;

class DbalActivityRepositoryTest extends DatabaseTestCase
{
    use MatchesSnapshots;

    private ActivityRepository $activityRepository;

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

        $this->assertMatchesJsonSnapshot(
            $this->getConnectionFactory()
                ->getForYear(Year::fromDate($activity->getStartDate()))
                ->executeQuery('SELECT * FROM Activity')->fetchAllAssociative()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityRepository = new DbalActivityRepository(
            $this->getConnectionFactory()
        );
    }
}