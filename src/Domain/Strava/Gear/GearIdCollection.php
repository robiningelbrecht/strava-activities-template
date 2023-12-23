<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

use App\Infrastructure\ValueObject\Collection;

final class GearIdCollection extends Collection
{
    public function getItemClassName(): string
    {
        return GearId::class;
    }
}
