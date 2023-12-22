<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort\ReadModel;

use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortCollection;
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

    public function find(int $id): SegmentEffort
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('SegmentEffort')
            ->andWhere('segmentEffortId = :segmentEffortId')
            ->setParameter('segmentEffortId', $id);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('segmentEffort "%s" not found', $id));
        }

        return $this->buildFromResult($result);
    }

    public function findBySegmentId(int $segmentId): SegmentEffortCollection
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

    public function findByActivityId(int $activityId): SegmentEffortCollection
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
            segmentEffortId: (int) $result['segmentEffortId'],
            segmentId: (int) $result['segmentId'],
            activityId: (int) $result['activityId'],
            startDateTime: SerializableDateTime::fromString($result['startDateTime']),
            data: Json::decode($result['data']),
        );
    }
}
