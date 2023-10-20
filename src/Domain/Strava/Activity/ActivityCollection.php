<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Activity>
 */
final class ActivityCollection extends Collection
{
    public function getItemClassName(): string
    {
        return Activity::class;
    }
}
