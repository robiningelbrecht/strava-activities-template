<?php

declare(strict_types=1);

namespace App\Domain\Strava\Calendar;

use App\Infrastructure\ValueObject\Collection;

class DayCollection extends Collection
{
    public function getItemClassName(): string
    {
        return Day::class;
    }
}
