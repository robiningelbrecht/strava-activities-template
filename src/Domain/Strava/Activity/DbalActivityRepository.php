<?php

namespace App\Domain\Strava\Activity;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use Doctrine\DBAL\Connection;

final class DbalActivityRepository implements ActivityRepository
{
    /** @var array<int|string, \App\Domain\Strava\Activity\ActivityCollection> */
    public static array $cachedActivities = [];

    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function find(int $id): Activity
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Activity')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $id);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Activity "%s" not found', $id));
        }

        return $this->buildFromResult($result);
    }

    public function findAll(int $limit = null): ActivityCollection
    {
        $cacheKey = $limit ?? 'all';
        if (array_key_exists($cacheKey, DbalActivityRepository::$cachedActivities)) {
            return DbalActivityRepository::$cachedActivities[$cacheKey];
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC')
            ->setMaxResults($limit);

        $activities = array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        );
        DbalActivityRepository::$cachedActivities[$cacheKey] = ActivityCollection::fromArray($activities);

        return DbalActivityRepository::$cachedActivities[$cacheKey];
    }

    /**
     * @return int[]
     */
    public function findActivityIds(): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('activityId')
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC');

        return $queryBuilder->executeQuery()->fetchFirstColumn();
    }

    /**
     * @return string[]
     */
    public function findUniqueGearIds(): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('gearId')
            ->distinct()
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC');

        return $queryBuilder->executeQuery()->fetchFirstColumn();
    }

    /**
     * @return \App\Infrastructure\ValueObject\Time\Year[]
     */
    public function findUniqueYears(): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select("STRFTIME('%Y', startDateTime)")
            ->distinct()
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC');

        return array_map(
            callback: fn (int $year) => Year::fromInt($year),
            array: $queryBuilder->executeQuery()->fetchFirstColumn()
        );
    }

    public function add(Activity $activity): void
    {
        $sql = 'INSERT INTO Activity (activityId, startDateTime, data, weather, gearId)
        VALUES (:activityId, :startDateTime, :data, :weather, :gearId)';

        $this->connection->executeStatement($sql, [
            'activityId' => $activity->getId(),
            'startDateTime' => $activity->getStartDate(),
            'data' => Json::encode($this->cleanData($activity->getData())),
            'weather' => Json::encode($activity->getAllWeatherData()),
            'gearId' => $activity->getGearId(),
        ]);
    }

    public function update(Activity $activity): void
    {
        $sql = 'UPDATE Activity 
        SET data = :data, gearId = :gearId
        WHERE activityId = :activityId';

        $this->connection->executeStatement($sql, [
            'activityId' => $activity->getId(),
            'data' => Json::encode($this->cleanData($activity->getData())),
            'gearId' => $activity->getGearId(),
        ]);
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private function cleanData(array $data): array
    {
        if (isset($data['map']['polyline'])) {
            unset($data['map']['polyline']);
        }
        if (isset($data['laps'])) {
            unset($data['laps']);
        }
        if (isset($data['splits_standard'])) {
            unset($data['splits_standard']);
        }
        if (isset($data['splits_metric'])) {
            unset($data['splits_metric']);
        }
        if (isset($data['stats_visibility'])) {
            unset($data['stats_visibility']);
        }

        return $data;
    }

    /**
     * @param array<mixed> $result
     */
    private function buildFromResult(array $result): Activity
    {
        return Activity::fromState(
            activityId: $result['activityId'],
            startDateTime: SerializableDateTime::fromString($result['startDateTime']),
            data: Json::decode($result['data']),
            weather: Json::decode($result['weather'] ?? '[]'),
            gearId: $result['gearId']
        );
    }
}
