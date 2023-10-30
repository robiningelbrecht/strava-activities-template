<?php

namespace App\Domain\Strava\Challenge;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalChallengeRepository implements ChallengeRepository
{
    public function __construct(
        private Connection $connection
    ) {
    }

    public function findAll(): ChallengeCollection
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Challenge')
            ->orderBy('createdOn', 'DESC');

        return ChallengeCollection::fromArray(array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function find(string $id): Challenge
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Challenge')
            ->andWhere('challengeId = :challengeId')
            ->setParameter('challengeId', $id);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Challenge "%s" not found', $id));
        }

        return $this->buildFromResult($result);
    }

    public function add(Challenge $challenge): void
    {
        $sql = 'INSERT INTO Challenge (challengeId, createdOn, data)
        VALUES (:challengeId, :createdOn, :data)';

        $this->connection->executeStatement($sql, [
            'challengeId' => $challenge->getId(),
            'createdOn' => $challenge->getCreatedOn(),
            'data' => Json::encode($challenge->getData()),
        ]);
    }

    /**
     * @param array<mixed> $result
     */
    private function buildFromResult(array $result): Challenge
    {
        return Challenge::fromState(
            challengeId: $result['challengeId'],
            createdOn: SerializableDateTime::fromString($result['createdOn']),
            data: Json::decode($result['data']),
        );
    }
}
