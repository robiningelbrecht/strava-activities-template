<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Gear>
 */
final class GearCollection extends Collection
{
    public function getItemClassName(): string
    {
        return Gear::class;
    }

    public function sortByIsRetired(): self
    {
        $items = $this->toArray();

        usort($items, function (Gear $a, Gear $b) {
            return $a->isRetired() <=> $b->isRetired();
        });

        return self::fromArray($items);
    }
}
