<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort\ReadModel;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortCollection;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Strava\Segment\SegmentId;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalSegmentEffortDetailsRepository implements SegmentEffortDetailsRepository
{
    private Connection $connection;

    public function __construct(
        ConnectionFactory $connectionFactory
    ) {
        $this->connection = $connectionFactory->getReadOnly();
    }

    public function find(SegmentEffortId $segmentEffortId): SegmentEffort
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('SegmentEffort')
            ->andWhere('segmentEffortId = :segmentEffortId')
            ->setParameter('segmentEffortId', $segmentEffortId);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('segmentEffort "%s" not found', $segmentEffortId));
        }

        return $this->buildFromResult($result);
    }

    public function findBySegmentId(SegmentId $segmentId): SegmentEffortCollection
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('SegmentEffort')
            ->andWhere('segmentId = :segmentId')
            ->setParameter('segmentId', $segmentId)
            ->orderBy("JSON_EXTRACT(data, '$.elapsed_time')", 'ASC');

        return SegmentEffortCollection::fromArray(array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findByActivityId(ActivityId $activityId): SegmentEffortCollection
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('SegmentEffort')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId);

        return SegmentEffortCollection::fromArray(array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    /**
     * @param array<mixed> $result
     */
    private function buildFromResult(array $result): SegmentEffort
    {
        return SegmentEffort::fromState(
            segmentEffortId: SegmentEffortId::fromString($result['segmentEffortId']),
            segmentId: SegmentId::fromString($result['segmentId']),
            activityId: ActivityId::fromString($result['activityId']),
            startDateTime: SerializableDateTime::fromString($result['startDateTime']),
            data: Json::decode($result['data']),
        );
    }
}
