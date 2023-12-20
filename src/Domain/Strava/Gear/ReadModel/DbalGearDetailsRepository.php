<?php

namespace App\Domain\Strava\Gear\ReadModel;

use App\Domain\Strava\Gear\Gear;
use App\Domain\Strava\Gear\GearCollection;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalGearDetailsRepository implements GearDetailsRepository
{
    private Connection $connection;

    public function __construct(
        ConnectionFactory $connectionFactory
    ) {
        $this->connection = $connectionFactory->getReadOnly();
    }

    public function findAll(): GearCollection
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Gear')
            ->orderBy('distanceInMeter', 'DESC');

        return GearCollection::fromArray(array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function find(string $id): Gear
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Gear')
            ->andWhere('gearId = :gearId')
            ->setParameter('gearId', $id);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Gear "%s" not found', $id));
        }

        return $this->buildFromResult($result);
    }

    /**
     * @param array<mixed> $result
     */
    private function buildFromResult(array $result): Gear
    {
        return Gear::fromState(
            gearId: $result['gearId'],
            data: Json::decode($result['data']),
            distanceInMeter: $result['distanceInMeter'],
            createdOn: SerializableDateTime::fromString($result['createdOn']),
        );
    }
}
