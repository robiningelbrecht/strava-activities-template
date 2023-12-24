<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

use App\Infrastructure\ValueObject\String\Identifier;

final readonly class GearId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'gear-';
    }
}
