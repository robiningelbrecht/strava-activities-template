<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Infrastructure\Repository\ProvideSqlConvert;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalActivityStreamRepository implements ActivityStreamRepository
{
    use ProvideSqlConvert;

    public function __construct(
        private Connection $connection
    ) {
    }

    public function hasOneForActivity(int $activityId): bool
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId);

        return !empty($queryBuilder->executeQuery()->fetchOne());
    }

    public function hasOneForActivityAndStreamType(int $activityId, StreamType $streamType): bool
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId)
            ->andWhere('streamType = :streamType')
            ->setParameter('streamType', $streamType->value);

        return !empty($queryBuilder->executeQuery()->fetchOne());
    }

    public function findByStreamType(StreamType $streamType): ActivityStreamCollection
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('streamType = :streamType')
            ->setParameter('streamType', $streamType->value);

        return ActivityStreamCollection::fromArray(array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findByActivityAndStreamTypes(int $activityId, StreamTypeCollection $streamTypes): ActivityStreamCollection
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId)
            ->andWhere('streamType IN ('.$this->toWhereInValueForCollection($streamTypes).')');

        return ActivityStreamCollection::fromArray(array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function add(ActivityStream $stream): void
    {
        $sql = 'INSERT INTO ActivityStream (activityId, streamType, data, createdOn)
        VALUES (:activityId, :streamType, :data, :createdOn)';

        $this->connection->executeStatement($sql, [
            'activityId' => $stream->getActivityId(),
            'streamType' => $stream->getStreamType()->value,
            'data' => Json::encode($stream->getData()),
            'createdOn' => $stream->getCreatedOn(),
        ]);
    }

    /**
     * @param array<mixed> $result
     */
    private function buildFromResult(array $result): ActivityStream
    {
        return DefaultStream::fromState(
            activityId: $result['activityId'],
            streamType: StreamType::from($result['streamType']),
            streamData: Json::decode($result['data']),
            createdOn: SerializableDateTime::fromString($result['createdOn']),
        );
    }
}
