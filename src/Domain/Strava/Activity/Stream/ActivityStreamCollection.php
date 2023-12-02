<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<ActivityStream>
 */
class ActivityStreamCollection extends Collection
{
    public function getItemClassName(): string
    {
        return ActivityStream::class;
    }

    public function getByStreamType(StreamType $streamType): ?ActivityStream
    {
        if ($steams = array_filter(
            $this->toArray(),
            fn (ActivityStream $stream) => $streamType === $stream->getStreamType()
        )) {
            return reset($steams);
        }

        return null;
    }
}
