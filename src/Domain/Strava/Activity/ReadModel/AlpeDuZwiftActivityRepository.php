<?php

namespace App\Domain\Strava\Activity\ReadModel;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityIds;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use Doctrine\DBAL\Connection;

final readonly class AlpeDuZwiftActivityRepository
{
    private Connection $connection;

    public function __construct(
        ConnectionFactory $connectionFactory,
    ) {
        $this->connection = $connectionFactory->getReadOnly();
    }

    public function findActivityIds(): ActivityIds
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('Activity.activityId')
            ->from('Activity')
            ->innerJoin('Activity', 'SegmentEffort', 'SegmentEffort', 'Activity.activityId = SegmentEffort.activityId AND SegmentId = :segmentId')
            ->setParameter('segmentId', 'segment-17267489')
            ->orderBy('Activity.startDateTime', 'DESC');

        return ActivityIds::fromArray(array_map(
            fn (string $id) => ActivityId::fromString($id),
            $queryBuilder->executeQuery()->fetchFirstColumn(),
        ));
    }
}
