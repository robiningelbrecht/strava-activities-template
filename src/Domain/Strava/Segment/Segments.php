<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Segment>
 */
final class Segments extends Collection
{
    public function getItemClassName(): string
    {
        return Segment::class;
    }

    public function getAlpeDuZwiftSegment(): ?Segment
    {
        foreach ($this as $segment) {
            if ($segment->getId() == SegmentId::fromUnprefixed('17267489')) {
                return $segment;
            }
        }

        return null;
    }
}
