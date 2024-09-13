<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\WriteModel;

use App\Domain\Strava\Segment\Segment;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Serialization\Json;

final readonly class DbalSegmentRepository implements SegmentRepository
{
    public function __construct(
        private ConnectionFactory $connectionFactory,
    ) {
    }

    public function add(Segment $segment): void
    {
        $sql = 'INSERT INTO Segment (segmentId, name, data)
        VALUES (:segmentId, :name, :data)';

        $this->connectionFactory->getDefault()->executeStatement($sql, [
            'segmentId' => $segment->getId(),
            'name' => $segment->getName(),
            'data' => Json::encode($segment->getData()),
        ]);
    }
}
