<?php

namespace App\Domain\Strava\Activity\Stream;

use SleekDB\Store;

final readonly class StravaActivityStreamRepository
{
    public function __construct(
        private Store $store
    ) {
    }

    public function hasOneForActivity(int $activityId): bool
    {
        if (!$this->store->findOneBy(['activityId', '==', $activityId])) {
            return false;
        }

        return true;
    }

    public function findByStreamType(StreamType $streamType): array
    {
        return array_map(
            fn (array $row) => DefaultStream::fromMap($row),
            $this->store->findBy([
                ['type', '==', $streamType->value],
            ])
        );
    }

    public function findByActivityAndStreamTypes(string $activityId, array $streamTypes): array
    {
        return array_map(
            fn (array $row) => DefaultStream::fromMap($row),
            $this->store->findBy([
                ['activityId', '==', $activityId],
                ['type', 'IN', array_map(fn (StreamType $stream) => $stream->value, $streamTypes)],
            ])
        );
    }

    public function add(ActivityStream $stream): void
    {
        $this->store->insert($stream->jsonSerialize());
    }
}
