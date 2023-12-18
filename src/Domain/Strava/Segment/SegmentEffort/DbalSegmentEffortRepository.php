<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalSegmentEffortRepository implements SegmentEffortRepository
{
    public function __construct(
        private Connection $connection
    ) {
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
            ->orderBy('startDateTime', 'DESC');

        return SegmentEffortCollection::fromArray(array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function add(SegmentEffort $segmentEffort): void
    {
        $sql = 'INSERT INTO SegmentEffort (segmentEffortId, segmentId, activityId, startDateTime, data)
        VALUES (:segmentEffortId, :segmentId, :activityId, :startDateTime, :data)';

        $data = $segmentEffort->getData();
        if (isset($data['segment'])) {
            unset($data['segment']);
        }

        $this->connection->executeStatement($sql, [
            'segmentEffortId' => $segmentEffort->getId(),
            'segmentId' => $segmentEffort->getSegmentId(),
            'activityId' => $segmentEffort->getActivityId(),
            'startDateTime' => $segmentEffort->getStartDateTime(),
            'data' => Json::encode($data),
        ]);
    }

    public function update(SegmentEffort $segmentEffort): void
    {
        $sql = 'UPDATE SegmentEffort 
        SET data = :data
        WHERE segmentEffortId = :segmentEffortId';

        $data = $segmentEffort->getData();
        if (isset($data['segment'])) {
            unset($data['segment']);
        }

        $this->connection->executeStatement($sql, [
            'segmentEffortId' => $segmentEffort->getId(),
            'data' => Json::encode($data),
        ]);
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
