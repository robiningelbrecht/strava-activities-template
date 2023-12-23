<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Infrastructure\ValueObject\String\Identifier;

final readonly class ActivityId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'activity-';
    }
}
