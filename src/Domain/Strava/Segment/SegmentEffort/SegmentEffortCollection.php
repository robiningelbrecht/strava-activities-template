<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<SegmentEffort>
 */
final class SegmentEffortCollection extends Collection
{
    public function getItemClassName(): string
    {
        return SegmentEffort::class;
    }

    public function getBestEffort(): SegmentEffort
    {
        /** @var \App\Domain\Strava\Segment\SegmentEffort\SegmentEffort $bestEffort */
        $bestEffort = $this->getFirst();
        /** @var \App\Domain\Strava\Segment\SegmentEffort\SegmentEffort $segmentEffort */
        foreach ($this as $segmentEffort) {
            if ($segmentEffort->getElapsedTimeInSeconds() >= $bestEffort->getElapsedTimeInSeconds()) {
                continue;
            }
            $bestEffort = $segmentEffort;
        }

        return $bestEffort;
    }
}
