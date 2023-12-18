<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Segment>
 */
final class SegmentCollection extends Collection
{
    public function getItemClassName(): string
    {
        return Segment::class;
    }
}
