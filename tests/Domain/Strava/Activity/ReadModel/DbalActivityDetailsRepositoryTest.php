<?php

namespace App\Tests\Domain\Strava\Activity\ReadModel;

use App\Domain\Strava\Activity\ActivityCollection;
use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Domain\Strava\Activity\ReadModel\DbalActivityDetailsRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\DatabaseTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;

class DbalActivityDetailsRepositoryTest extends DatabaseTestCase
{
    private ActivityDetailsRepository $activityDetailsRepository;

    public function testFind(): void
    {
        $activity = ActivityBuilder::fromDefaults()->build();

        $sql = 'INSERT INTO Activity (activityId, startDateTime, data, weather, gearId)
        VALUES (:activityId, :startDateTime, :data, :weather, :gearId)';

        $this->getConnectionFactory()->getReadOnly()->executeStatement($sql, [
            'activityId' => $activity->getId(),
            'startDateTime' => $activity->getStartDate(),
            'data' => Json::encode($activity->getData()),
            'weather' => Json::encode($activity->getAllWeatherData()),
            'gearId' => $activity->getGearId(),
        ]);

        $this->assertEquals(
            $activity,
            $this->activityDetailsRepository->find($activity->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->activityDetailsRepository->find(1);
    }

    public function testFindAll(): void
    {
        $sql = 'INSERT INTO Activity (activityId, startDateTime, data, weather, gearId)
        VALUES (:activityId, :startDateTime, :data, :weather, :gearId)';

        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(1)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->getConnectionFactory()->getReadOnly()->executeStatement($sql, [
            'activityId' => $activityOne->getId(),
            'startDateTime' => $activityOne->getStartDate(),
            'data' => Json::encode($activityOne->getData()),
            'weather' => Json::encode($activityOne->getAllWeatherData()),
            'gearId' => $activityOne->getGearId(),
        ]);
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(2)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->getConnectionFactory()->getReadOnly()->executeStatement($sql, [
            'activityId' => $activityTwo->getId(),
            'startDateTime' => $activityTwo->getStartDate(),
            'data' => Json::encode($activityTwo->getData()),
            'weather' => Json::encode($activityTwo->getAllWeatherData()),
            'gearId' => $activityTwo->getGearId(),
        ]);
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(3)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->getConnectionFactory()->getReadOnly()->executeStatement($sql, [
            'activityId' => $activityThree->getId(),
            'startDateTime' => $activityThree->getStartDate(),
            'data' => Json::encode($activityThree->getData()),
            'weather' => Json::encode($activityThree->getAllWeatherData()),
            'gearId' => $activityThree->getGearId(),
        ]);

        $this->assertEquals(
            ActivityCollection::fromArray([$activityOne, $activityTwo, $activityThree]),
            $this->activityDetailsRepository->findAll()
        );
    }

    public function testFindActivityIds(): void
    {
        $sql = 'INSERT INTO Activity (activityId, startDateTime, data, weather, gearId)
        VALUES (:activityId, :startDateTime, :data, :weather, :gearId)';

        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(1)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->getConnectionFactory()->getReadOnly()->executeStatement($sql, [
            'activityId' => $activityOne->getId(),
            'startDateTime' => $activityOne->getStartDate(),
            'data' => Json::encode($activityOne->getData()),
            'weather' => Json::encode($activityOne->getAllWeatherData()),
            'gearId' => $activityOne->getGearId(),
        ]);
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(2)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->getConnectionFactory()->getReadOnly()->executeStatement($sql, [
            'activityId' => $activityTwo->getId(),
            'startDateTime' => $activityTwo->getStartDate(),
            'data' => Json::encode($activityTwo->getData()),
            'weather' => Json::encode($activityTwo->getAllWeatherData()),
            'gearId' => $activityTwo->getGearId(),
        ]);
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(3)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->getConnectionFactory()->getReadOnly()->executeStatement($sql, [
            'activityId' => $activityThree->getId(),
            'startDateTime' => $activityThree->getStartDate(),
            'data' => Json::encode($activityThree->getData()),
            'weather' => Json::encode($activityThree->getAllWeatherData()),
            'gearId' => $activityThree->getGearId(),
        ]);

        $this->assertEquals(
            [1, 2, 3],
            $this->activityDetailsRepository->findActivityIds()
        );
    }

    public function testFindUniqueGearIds(): void
    {
        $sql = 'INSERT INTO Activity (activityId, startDateTime, data, weather, gearId)
        VALUES (:activityId, :startDateTime, :data, :weather, :gearId)';

        $activityOne = ActivityBuilder::fromDefaults()
            ->withActivityId(1)
            ->withGearId(1)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->getConnectionFactory()->getReadOnly()->executeStatement($sql, [
            'activityId' => $activityOne->getId(),
            'startDateTime' => $activityOne->getStartDate(),
            'data' => Json::encode($activityOne->getData()),
            'weather' => Json::encode($activityOne->getAllWeatherData()),
            'gearId' => $activityOne->getGearId(),
        ]);
        $activityTwo = ActivityBuilder::fromDefaults()
            ->withActivityId(2)
            ->withGearId(2)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-10 13:00:34'))
            ->build();
        $this->getConnectionFactory()->getReadOnly()->executeStatement($sql, [
            'activityId' => $activityTwo->getId(),
            'startDateTime' => $activityTwo->getStartDate(),
            'data' => Json::encode($activityTwo->getData()),
            'weather' => Json::encode($activityTwo->getAllWeatherData()),
            'gearId' => $activityTwo->getGearId(),
        ]);
        $activityThree = ActivityBuilder::fromDefaults()
            ->withActivityId(3)
            ->withGearId(1)
            ->withStartDateTime(SerializableDateTime::fromString('2023-10-09 14:00:34'))
            ->build();
        $this->getConnectionFactory()->getReadOnly()->executeStatement($sql, [
            'activityId' => $activityThree->getId(),
            'startDateTime' => $activityThree->getStartDate(),
            'data' => Json::encode($activityThree->getData()),
            'weather' => Json::encode($activityThree->getAllWeatherData()),
            'gearId' => $activityThree->getGearId(),
        ]);

        $this->assertEquals(
            [1, 2],
            $this->activityDetailsRepository->findUniqueGearIds()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityDetailsRepository = new DbalActivityDetailsRepository(
            $this->getConnectionFactory()
        );
    }
}
