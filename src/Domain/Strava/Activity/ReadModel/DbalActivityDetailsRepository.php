<?php

namespace App\Domain\Strava\Activity\ReadModel;

use App\Domain\Nominatim\Location;
use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIdCollection;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\Gear\GearIdCollection;
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

    public function find(ActivityId $activityId): Activity
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Activity')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Activity "%s" not found', $activityId));
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

    public function findActivityIds(): ActivityIdCollection
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('activityId')
            ->from('Activity')
            ->orderBy('startDateTime', 'DESC');

        return ActivityIdCollection::fromArray(array_map(
            fn (string $id) => ActivityId::fromString($id),
            $queryBuilder->executeQuery()->fetchFirstColumn(),
        ));
    }

    public function findUniqueGearIds(): GearIdCollection
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('gearId')
            ->distinct()
            ->from('Activity')
            ->andWhere('gearId IS NOT NULL')
            ->orderBy('startDateTime', 'DESC');

        return GearIdCollection::fromArray(array_map(
            fn (string $id) => GearId::fromString($id),
            $queryBuilder->executeQuery()->fetchFirstColumn(),
        ));
    }

    public function findMostRiddenState(): ?string
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select("JSON_EXTRACT(location, '$.state') as state")
            ->from('Activity')
            ->andWhere('state IS NOT NULL')
            ->groupBy("JSON_EXTRACT(location, '$.state')")
            ->orderBy('COUNT(*)', 'DESC');

        return $queryBuilder->executeQuery()->fetchOne();
    }

    /**
     * @param array<mixed> $result
     */
    private function buildFromResult(array $result): Activity
    {
        $location = Json::decode($result['location'] ?? '[]');

        return Activity::fromState(
            activityId: ActivityId::fromString($result['activityId']),
            startDateTime: SerializableDateTime::fromString($result['startDateTime']),
            data: Json::decode($result['data']),
            location: $location ? Location::fromState($location) : null,
            weather: Json::decode($result['weather'] ?? '[]'),
            gearId: GearId::fromOptionalString($result['gearId']),
        );
    }
}
