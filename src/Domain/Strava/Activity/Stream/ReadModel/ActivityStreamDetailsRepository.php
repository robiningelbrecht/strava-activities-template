<?php

namespace App\Domain\Strava\Activity\Stream\ReadModel;

use App\Domain\Strava\Activity\Stream\ActivityStreamCollection;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypeCollection;

interface ActivityStreamDetailsRepository
{
    public function isImportedForActivity(int $activityId): bool;

    public function hasOneForActivityAndStreamType(int $activityId, StreamType $streamType): bool;

    public function findByStreamType(StreamType $streamType): ActivityStreamCollection;

    public function findByActivityAndStreamTypes(int $activityId, StreamTypeCollection $streamTypes): ActivityStreamCollection;

    public function findByActivityId(int $activityId): ActivityStreamCollection;
}
