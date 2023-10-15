<?php

namespace App\Domain\Strava\Activity;

use App\Infrastructure\Exception\EntityNotFound;
use SleekDB\Store;

final class StravaActivityRepository
{
    private static array $cachedActivities = [];

    public function __construct(
        private readonly Store $store
    ) {
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

        StravaActivityRepository::$cachedActivities[$cacheKey] = array_map(
            fn (array $row) => Activity::fromMap($row),
            $this->store->findAll(['start_date_timestamp' => 'desc'], $limit)
        );

        return StravaActivityRepository::$cachedActivities[$cacheKey];
    }

    public function findActivityIds(): array
    {
        return array_column(
            $this->store
                ->createQueryBuilder()
                ->select(['id'])
                ->getQuery()
                ->fetch(),
            'id'
        );
    }

    public function findUniqueGearIds(): array
    {
        return array_filter(array_column(
            $this->store
                ->createQueryBuilder()
                ->select(['gear_id'])
                ->distinct(['gear_id'])
                ->getQuery()
                ->fetch(),
            'gear_id'
        ));
    }

    public function findOneBy(int $id): Activity
    {
        if (!$row = $this->store->findOneBy(['id', '==', $id])) {
            throw new EntityNotFound(sprintf('Activity "%s" not found', $id));
        }

        return Activity::fromMap($row);
    }

    public function add(Activity $activity): void
    {
        $this->store->insert($activity->jsonSerialize());
    }

    public function update(Activity $activity): void
    {
        $this->store->update($activity->jsonSerialize());
    }
}
