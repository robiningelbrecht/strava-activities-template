<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\ReadModel;

use App\Domain\Strava\Segment\Segment;
use App\Domain\Strava\Segment\SegmentCollection;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\String\Name;
use Doctrine\DBAL\Connection;

final readonly class DbalSegmentDetailsRepository implements SegmentDetailsRepository
{
    private Connection $connection;

    public function __construct(
        ConnectionFactory $connectionFactory
    ) {
        $this->connection = $connectionFactory->getReadOnly();
    }

    public function find(int $id): Segment
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Segment')
            ->andWhere('segmentId = :segmentId')
            ->setParameter('segmentId', $id);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Segment "%s" not found', $id));
        }

        return $this->buildFromResult($result);
    }

    public function findAll(): SegmentCollection
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*', '(SELECT COUNT(*) FROM SegmentEffort WHERE SegmentEffort.segmentId = Segment.segmentId) as countCompleted')
            ->from('Segment')
            ->orderBy('countCompleted', 'DESC');

        return SegmentCollection::fromArray(array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    /**
     * @param array<mixed> $result
     */
    private function buildFromResult(array $result): Segment
    {
        return Segment::fromState(
            segmentId: (int) $result['segmentId'],
            name: Name::fromString($result['name']),
            data: Json::decode($result['data']),
        );
    }
}
