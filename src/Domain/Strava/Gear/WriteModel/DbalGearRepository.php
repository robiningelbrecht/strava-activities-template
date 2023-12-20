<?php

namespace App\Domain\Strava\Gear\WriteModel;

use App\Domain\Strava\Gear\Gear;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Serialization\Json;
use Doctrine\DBAL\Connection;

final readonly class DbalGearRepository implements GearRepository
{
    private Connection $connection;

    public function __construct(
        ConnectionFactory $connectionFactory
    ) {
        $this->connection = $connectionFactory->getDefault();
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
}
