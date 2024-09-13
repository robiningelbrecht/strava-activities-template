<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<SegmentEffort>
 */
final class SegmentEfforts extends Collection
{
    public function getItemClassName(): string
    {
        return SegmentEffort::class;
    }

    public function getBestEffort(): ?SegmentEffort
    {
        /** @var SegmentEffort $bestEffort */
        $bestEffort = $this->getFirst();
        /** @var SegmentEffort $segmentEffort */
        foreach ($this as $segmentEffort) {
            if ($segmentEffort->getElapsedTimeInSeconds() >= $bestEffort->getElapsedTimeInSeconds()) {
                continue;
            }
            $bestEffort = $segmentEffort;
        }

        return $bestEffort;
    }
}
