<?php

namespace App\Domain\Strava\Activity\WriteModel;

use App\Domain\Strava\Activity\Activity;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\Year;

final readonly class DbalActivityRepository implements ActivityRepository
{
    public function __construct(
        private ConnectionFactory $connectionFactory
    ) {
    }

    public function add(Activity $activity): void
    {
        $sql = 'INSERT INTO Activity (activityId, startDateTime, data, weather, gearId)
        VALUES (:activityId, :startDateTime, :data, :weather, :gearId)';

        $this->connectionFactory->getForYear(Year::fromDate($activity->getStartDate()))->executeStatement($sql, [
            'activityId' => $activity->getId(),
            'startDateTime' => $activity->getStartDate(),
            'data' => Json::encode($this->cleanData($activity->getData())),
            'weather' => Json::encode($activity->getAllWeatherData()),
            'gearId' => $activity->getGearId(),
        ]);
    }

    public function update(Activity $activity): void
    {
        $sql = 'UPDATE Activity 
        SET data = :data, gearId = :gearId
        WHERE activityId = :activityId';

        $this->connectionFactory->getForYear(Year::fromDate($activity->getStartDate()))->executeStatement($sql, [
            'activityId' => $activity->getId(),
            'data' => Json::encode($this->cleanData($activity->getData())),
            'gearId' => $activity->getGearId(),
        ]);
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private function cleanData(array $data): array
    {
        if (isset($data['map']['polyline'])) {
            unset($data['map']['polyline']);
        }
        if (isset($data['laps'])) {
            unset($data['laps']);
        }
        if (isset($data['splits_standard'])) {
            unset($data['splits_standard']);
        }
        if (isset($data['splits_metric'])) {
            unset($data['splits_metric']);
        }
        if (isset($data['stats_visibility'])) {
            unset($data['stats_visibility']);
        }

        return $data;
    }
}