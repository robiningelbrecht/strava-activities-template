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
}
