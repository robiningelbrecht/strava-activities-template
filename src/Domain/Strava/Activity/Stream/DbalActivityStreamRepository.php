<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Repository\ProvideSqlConvert;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalActivityStreamRepository implements ActivityStreamRepository
{
    use ProvideSqlConvert;

    public function __construct(
        private Connection $connection,
        private KeyValueStore $keyValueStore,
    ) {
    }

    public function isImportedForActivity(int $activityId): bool
    {
        try {
            $importedActivityStreams = explode(',', $this->keyValueStore->find(Key::IMPORTED_ACTIVITY_STREAMS)->getValue());
        } catch (EntityNotFound) {
            return false;
        }

        return in_array($activityId, $importedActivityStreams);
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

        // Keep track of activities we imported streams for.
        try {
            $importedActivityStreams = explode(',', $this->keyValueStore->find(Key::IMPORTED_ACTIVITY_STREAMS)->getValue());
        } catch (EntityNotFound) {
            $importedActivityStreams = [];
        }
        $activityId = (string) $stream->getActivityId();
        if (!in_array($activityId, $importedActivityStreams)) {
            $importedActivityStreams[] = $activityId;
        }

        $this->keyValueStore->save(KeyValue::fromState(
            key: Key::IMPORTED_ACTIVITY_STREAMS,
            value: Value::fromString(implode(',', $importedActivityStreams)),
        ));
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
