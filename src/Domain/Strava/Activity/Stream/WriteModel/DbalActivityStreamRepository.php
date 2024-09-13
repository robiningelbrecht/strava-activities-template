<?php

namespace App\Domain\Strava\Activity\Stream\WriteModel;

use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Repository\ProvideSqlConvert;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\Year;

final readonly class DbalActivityStreamRepository implements ActivityStreamRepository
{
    use ProvideSqlConvert;

    public function __construct(
        private ConnectionFactory $connectionFactory,
    ) {
    }

    public function add(ActivityStream $stream): void
    {
        $sql = 'INSERT INTO ActivityStream (activityId, streamType, data, createdOn, bestAverages)
        VALUES (:activityId, :streamType, :data, :createdOn, :bestAverages)';

        $this->connectionFactory->getForYear(Year::fromDate($stream->getCreatedOn()))->executeStatement($sql, [
            'activityId' => $stream->getActivityId(),
            'streamType' => $stream->getStreamType()->value,
            'data' => Json::encode($stream->getData()),
            'createdOn' => $stream->getCreatedOn(),
            'bestAverages' => !empty($stream->getBestAverages()) ? Json::encode($stream->getBestAverages()) : null,
        ]);
    }

    public function update(ActivityStream $stream): void
    {
        $sql = 'UPDATE ActivityStream 
        SET bestAverages = :bestAverages
        WHERE activityId = :activityId
        AND streamType = :streamType';

        $this->connectionFactory->getForYear(Year::fromDate($stream->getCreatedOn()))->executeStatement($sql, [
            'activityId' => $stream->getActivityId(),
            'streamType' => $stream->getStreamType()->value,
            'bestAverages' => Json::encode($stream->getBestAverages()),
        ]);
    }

    public function delete(ActivityStream $stream): void
    {
        $sql = 'DELETE FROM ActivityStream
        WHERE activityId = :activityId
        AND streamType = :streamType';

        $this->connectionFactory->getForYear(Year::fromDate($stream->getCreatedOn()))->executeStatement($sql, [
            'activityId' => $stream->getActivityId(),
            'streamType' => $stream->getStreamType()->value,
        ]);
    }
}
