<?php

namespace App\Domain\Strava\Gear;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class StravaGearRepository
{
    public function __construct(
        private Connection $connection
    ) {
    }

    /**
     * @return \App\Domain\Strava\Gear\Gear[]
     */
    public function findAll(): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Challenge')
            ->orderBy('distance', 'DESC');

        return array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        );
    }

    public function findOneBy(string $id): Gear
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
        $sql = 'INSERT INTO Gear (gearId, createdOn, data, distance)
        VALUES (:gearId, :createdOn, :data, :distance)';

        $this->connection->executeStatement($sql, [
            'gearId' => $gear->getId(),
            'createdOn' => $gear->getCreatedOn(),
            'data' => Json::encode($gear->getData()),
            'distance' => $gear->getDistance(),
        ]);
    }

    public function update(Gear $gear): void
    {
        $sql = 'UPDATE Gear 
        SET distance = :distance,
        data = :data
        WHERE gearId = :gearId';

        $this->connection->executeStatement($sql, [
            'gearId' => $gear->getId(),
            'distance' => $gear->getDistance(),
            'data' => Json::encode($gear->getData()),
        ]);
    }

    private function buildFromResult(array $result): Gear
    {
        return Gear::fromState(
            gearId: $result['gearId'],
            data: Json::decode($result['data']),
            distance: $result['distance'],
            createdOn: SerializableDateTime::fromString($result['createdOn']),
        );
    }
}
