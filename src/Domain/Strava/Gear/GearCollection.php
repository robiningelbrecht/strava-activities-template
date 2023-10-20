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
}
