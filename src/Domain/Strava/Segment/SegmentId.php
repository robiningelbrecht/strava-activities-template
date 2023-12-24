<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment;

use App\Infrastructure\ValueObject\String\Identifier;

final readonly class SegmentId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'segment-';
    }
}
