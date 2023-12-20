<?php

namespace App\Domain\Strava\Challenge\ReadModel;

use App\Domain\Strava\Challenge\Challenge;
use App\Domain\Strava\Challenge\ChallengeCollection;
use App\Domain\Strava\Challenge\ChallengeId;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalChallengeDetailsRepository implements ChallengeDetailsRepository
{
    private Connection $connection;

    public function __construct(
        ConnectionFactory $connectionFactory
    ) {
        $this->connection = $connectionFactory->getReadOnly();
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
