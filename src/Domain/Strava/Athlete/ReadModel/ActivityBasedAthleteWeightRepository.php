<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\ReadModel;

use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Weight;
use Doctrine\DBAL\Connection;

final readonly class ActivityBasedAthleteWeightRepository implements AthleteWeightRepository
{
    private Connection $connection;

    public function __construct(
        ConnectionFactory $connectionFactory,
    ) {
        $this->connection = $connectionFactory->getReadOnly();
    }

    public function find(SerializableDateTime $dateTime): ?Weight
    {
        $dateTime = SerializableDateTime::fromString($dateTime->format('Y-m-d'));
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('data')
            ->from('Activity')
            ->andWhere('startDateTime <= :date')
            ->setParameter('date', $dateTime)
            ->setMaxResults(1)
            ->orderBy('startDateTime', 'DESC');

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Athlete weight for date "%s" not found', $dateTime));
        }

        return Weight::fromKilograms(Json::decode($result['data'])['athlete_weight'] ?? null);
    }
}
