<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort\WriteModel;

use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\Year;

final readonly class DbalSegmentEffortRepository implements SegmentEffortRepository
{
    public function __construct(
        private ConnectionFactory $connectionFactory
    ) {
    }

    public function add(SegmentEffort $segmentEffort): void
    {
        $sql = 'INSERT INTO SegmentEffort (segmentEffortId, segmentId, activityId, startDateTime, data)
        VALUES (:segmentEffortId, :segmentId, :activityId, :startDateTime, :data)';

        $data = $segmentEffort->getData();
        if (isset($data['segment'])) {
            unset($data['segment']);
        }

        $this->connectionFactory->getForYear(Year::fromDate($segmentEffort->getStartDateTime()))->executeStatement($sql, [
            'segmentEffortId' => $segmentEffort->getId(),
            'segmentId' => $segmentEffort->getSegmentId(),
            'activityId' => $segmentEffort->getActivityId(),
            'startDateTime' => $segmentEffort->getStartDateTime(),
            'data' => Json::encode($data),
        ]);
    }

    public function update(SegmentEffort $segmentEffort): void
    {
        $sql = 'UPDATE SegmentEffort 
        SET data = :data
        WHERE segmentEffortId = :segmentEffortId';

        $data = $segmentEffort->getData();
        if (isset($data['segment'])) {
            unset($data['segment']);
        }

        $this->connectionFactory->getForYear(Year::fromDate($segmentEffort->getStartDateTime()))->executeStatement($sql, [
            'segmentEffortId' => $segmentEffort->getId(),
            'data' => Json::encode($data),
        ]);
    }

    public function delete(SegmentEffort $segmentEffort): void
    {
        $sql = 'DELETE FROM SegmentEffort 
        WHERE segmentEffortId = :segmentEffortId';

        $this->connectionFactory->getForYear(Year::fromDate($segmentEffort->getStartDateTime()))->executeStatement($sql, [
            'segmentEffortId' => $segmentEffort->getId(),
        ]);
    }
}
