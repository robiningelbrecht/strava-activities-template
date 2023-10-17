<?php

namespace App\Domain\Strava\Activity;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final class StravaActivityRepository
{
    /** @var array<int|string, array<Activity>> */
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

    /**
     * @return \App\Domain\Strava\Activity\Activity[]
     */
    public function findAll(int $limit = null): array
    {
        $cacheKey = $limit ?? 'all';
        if (array_key_exists($cacheKey, StravaActivityRepository::$cachedActivities)) {
            return StravaActivityRepository::$cachedActivities[$cacheKey];
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC')
            ->setMaxResults($limit);

        StravaActivityRepository::$cachedActivities[$cacheKey] = array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        );

        return StravaActivityRepository::$cachedActivities[$cacheKey];
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

    public function add(Activity $activity): void
    {
        $sql = 'INSERT INTO Activity (activityId, startDateTime, data, gearId)
        VALUES (:activityId, :startDateTime, :data, :gearId)';

        $this->connection->executeStatement($sql, [
            'activityId' => $activity->getId(),
            'startDateTime' => $activity->getStartDate(),
            'data' => Json::encode($activity->getData()),
            'gearId' => $activity->getGearId(),
        ]);
    }

    public function update(Activity $activity): void
    {
        $sql = 'UPDATE Activity 
        SET data = :data
        WHERE activityId = :activityId';

        $this->connection->executeStatement($sql, [
            'activityId' => $activity->getId(),
            'data' => Json::encode($activity->getData()),
        ]);
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
            gearId: $result['gearId']
        );
    }
}
