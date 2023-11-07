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

    public function find(ChallengeId $challengeId): Challenge
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Challenge')
            ->andWhere('challengeId = :challengeId')
            ->setParameter('challengeId', (string) $challengeId);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Challenge "%s" not found', $challengeId));
        }

        return $this->buildFromResult($result);
    }

    public function add(Challenge $challenge): void
    {
        $sql = 'INSERT INTO Challenge (challengeId, createdOn, data)
        VALUES (:challengeId, :createdOn, :data)';

        $this->connection->executeStatement($sql, [
            'challengeId' => (string) $challenge->getId(),
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
            challengeId: ChallengeId::fromString($result['challengeId']),
            createdOn: SerializableDateTime::fromString($result['createdOn']),
            data: Json::decode($result['data']),
        );
    }
}
