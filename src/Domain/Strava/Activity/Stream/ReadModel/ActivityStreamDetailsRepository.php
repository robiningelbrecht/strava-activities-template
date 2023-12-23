<?php

namespace App\Domain\Strava\Activity\Stream\ReadModel;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\Stream\ActivityStreamCollection;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypeCollection;

interface ActivityStreamDetailsRepository
{
    public function isImportedForActivity(ActivityId $activityId): bool;

    public function hasOneForActivityAndStreamType(ActivityId $activityId, StreamType $streamType): bool;

    public function findByStreamType(StreamType $streamType): ActivityStreamCollection;

    public function findByActivityAndStreamTypes(ActivityId $activityId, StreamTypeCollection $streamTypes): ActivityStreamCollection;

    public function findByActivityId(ActivityId $activityId): ActivityStreamCollection;
}
