<?php

namespace App\Domain\Strava\Activity\ReadModel;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final class DbalActivityDetailsRepository implements ActivityDetailsRepository
{
    /** @var array<int|string, \App\Domain\Strava\Activity\ActivityCollection> */
    public static array $cachedActivities = [];
    private readonly Connection $connection;

    public function __construct(
        ConnectionFactory $connectionFactory
    ) {
        $this->connection = $connectionFactory->getReadOnly();
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
        if (array_key_exists($cacheKey, DbalActivityDetailsRepository::$cachedActivities)) {
            return DbalActivityDetailsRepository::$cachedActivities[$cacheKey];
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
        DbalActivityDetailsRepository::$cachedActivities[$cacheKey] = ActivityCollection::fromArray($activities);

        return DbalActivityDetailsRepository::$cachedActivities[$cacheKey];
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
