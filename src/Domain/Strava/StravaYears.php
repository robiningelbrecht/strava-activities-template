<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\YearCollection;
use Lcobucci\Clock\Clock;

class StravaYears
{
    public function __construct(
        private readonly Clock $clock
    ) {
    }

    public function getYears(): YearCollection
    {
        $years = YearCollection::empty();
        for ($i = 2000; $i <= (int) $this->clock->now()->format('Y'); ++$i) {
            $years->add(Year::fromInt($i));
        }

        return $years;
    }
}
