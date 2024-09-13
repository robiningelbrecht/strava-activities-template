<?php

namespace App\Domain\Strava\Activity\Stream\ReadModel;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Domain\Strava\Activity\Stream\ActivityStreams;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypes;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Repository\ProvideSqlConvert;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalActivityStreamDetailsRepository implements ActivityStreamDetailsRepository
{
    use ProvideSqlConvert;

    private Connection $connection;

    public function __construct(
        ConnectionFactory $connectionFactory,
    ) {
        $this->connection = $connectionFactory->getReadOnly();
    }

    public function isImportedForActivity(ActivityId $activityId): bool
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId);

        return !empty($queryBuilder->executeQuery()->fetchOne());
    }

    public function hasOneForActivityAndStreamType(ActivityId $activityId, StreamType $streamType): bool
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

    public function findByStreamType(StreamType $streamType): ActivityStreams
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('streamType = :streamType')
            ->setParameter('streamType', $streamType->value);

        return ActivityStreams::fromArray(array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findByActivityAndStreamTypes(ActivityId $activityId, StreamTypes $streamTypes): ActivityStreams
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId)
            ->andWhere('streamType IN ('.$this->toWhereInValueForCollection($streamTypes).')');

        return ActivityStreams::fromArray(array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findByActivityId(ActivityId $activityId): ActivityStreams
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('activityId = :activityId')
            ->setParameter('activityId', $activityId);

        return ActivityStreams::fromArray(array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findWithoutBestAverages(): ActivityStreams
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('bestAverages IS NULL');

        return ActivityStreams::fromArray(array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function findWithBestAverageFor(int $intervalInSeconds, StreamType $streamType): ActivityStream
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('ActivityStream')
            ->andWhere('streamType = :streamType')
            ->setParameter('streamType', $streamType->value)
            ->andWhere('JSON_EXTRACT(bestAverages, "$.'.$intervalInSeconds.'") IS NOT NULL')
            ->orderBy('JSON_EXTRACT(bestAverages, "$.'.$intervalInSeconds.'")', 'DESC')
            ->addOrderBy('createdOn', 'DESC')
            ->setMaxResults(1);

        if (!$result = $queryBuilder->fetchAssociative()) {
            throw new EntityNotFound('ActivityStream for average not found');
        }

        return $this->buildFromResult($result);
    }

    /**
     * @param array<mixed> $result
     */
    private function buildFromResult(array $result): ActivityStream
    {
        return ActivityStream::fromState(
            activityId: ActivityId::fromString($result['activityId']),
            streamType: StreamType::from($result['streamType']),
            streamData: Json::decode($result['data']),
            createdOn: SerializableDateTime::fromString($result['createdOn']),
            bestAverages: Json::decode($result['bestAverages'] ?: '[]'),
        );
    }
}
