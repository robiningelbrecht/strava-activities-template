<?php

namespace App\Domain\Strava\Activity\Stream\ReadModel;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Domain\Strava\Activity\Stream\ActivityStreams;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypes;

interface ActivityStreamDetailsRepository
{
    public function isImportedForActivity(ActivityId $activityId): bool;

    public function hasOneForActivityAndStreamType(ActivityId $activityId, StreamType $streamType): bool;

    public function findByStreamType(StreamType $streamType): ActivityStreams;

    public function findByActivityAndStreamTypes(ActivityId $activityId, StreamTypes $streamTypes): ActivityStreams;

    public function findByActivityId(ActivityId $activityId): ActivityStreams;

    public function findWithoutBestAverages(): ActivityStreams;

    public function findWithBestAverageFor(int $intervalInSeconds, StreamType $streamType): ActivityStream;
}
