<?php

namespace App\Domain\Strava\Activity\Stream;

interface ActivityStreamRepository
{
    public function hasOneForActivity(int $activityId): bool;

    public function hasOneForActivityAndStreamType(int $activityId, StreamType $streamType): bool;

    public function findByStreamType(StreamType $streamType): ActivityStreamCollection;

    public function findByActivityAndStreamTypes(int $activityId, StreamTypeCollection $streamTypes): ActivityStreamCollection;

    public function add(ActivityStream $stream): void;
}
