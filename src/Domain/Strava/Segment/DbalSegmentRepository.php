<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\String\Name;
use Doctrine\DBAL\Connection;

final readonly class DbalSegmentRepository implements SegmentRepository
{
    public function __construct(
        private Connection $connection
    ) {
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
        $queryBuilder->select('*')
            ->from('Segment')
            ->orderBy('name', 'ASC');

        return SegmentCollection::fromArray(array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function add(Segment $segment): void
    {
        $sql = 'INSERT INTO Segment (segmentId, name, data)
        VALUES (:segmentId, :name, :data)';

        $this->connection->executeStatement($sql, [
            'segmentId' => $segment->getId(),
            'name' => $segment->getName(),
            'data' => Json::encode($segment->getData()),
        ]);
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
