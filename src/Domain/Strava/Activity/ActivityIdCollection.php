<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Infrastructure\ValueObject\Collection;

final class ActivityIdCollection extends Collection
{
    public function getItemClassName(): string
    {
        return ActivityId::class;
    }
}
