<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort;

use App\Infrastructure\ValueObject\String\Identifier;

final readonly class SegmentEffortId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'segmentEffort-';
    }
}
