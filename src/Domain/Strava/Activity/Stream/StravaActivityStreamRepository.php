<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class StravaActivityStreamRepository
{
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

    public function findByStreamType(StreamType $streamType): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('streamType = :streamType')
            ->setParameter('streamType', $streamType->value);

        return array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        );
    }

    public function findByActivityAndStreamTypes(string $activityId, array $streamTypes): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId)
            ->andWhere('streamType IN (:streamTypes)')
            ->setParameter('streamTypes', array_map(fn (StreamType $stream) => $stream->value, $streamTypes));

        return array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        );
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
