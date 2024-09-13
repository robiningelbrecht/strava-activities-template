<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Gear>
 */
final class Gears extends Collection
{
    public function getItemClassName(): string
    {
        return Gear::class;
    }

    public function sortByIsRetired(): self
    {
        return $this->usort(function (Gear $a, Gear $b) {
            return $a->isRetired() <=> $b->isRetired();
        });
    }
}
