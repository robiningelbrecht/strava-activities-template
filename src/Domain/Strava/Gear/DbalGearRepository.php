<?php

namespace App\Domain\Strava\Gear;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalGearRepository implements GearRepository
{
    public function __construct(
        private Connection $connection
    ) {
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

    public function add(Gear $gear): void
    {
        $sql = 'INSERT INTO Gear (gearId, createdOn, data, distanceInMeter)
        VALUES (:gearId, :createdOn, :data, :distanceInMeter)';

        $this->connection->executeStatement($sql, [
            'gearId' => $gear->getId(),
            'createdOn' => $gear->getCreatedOn(),
            'data' => Json::encode($gear->getData()),
            'distanceInMeter' => $gear->getDistanceInMeter(),
        ]);
    }

    public function update(Gear $gear): void
    {
        $sql = 'UPDATE Gear 
        SET distanceInMeter = :distanceInMeter,
        data = :data
        WHERE gearId = :gearId';

        $this->connection->executeStatement($sql, [
            'gearId' => $gear->getId(),
            'distanceInMeter' => $gear->getDistanceInMeter(),
            'data' => Json::encode($gear->getData()),
        ]);
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
