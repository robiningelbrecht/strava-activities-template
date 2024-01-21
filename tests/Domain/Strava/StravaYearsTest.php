<?php

namespace App\Tests\Domain\Strava;

use App\Domain\Strava\StravaYears;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\YearCollection;
use App\Tests\PausedClock;
use PHPUnit\Framework\TestCase;

class StravaYearsTest extends TestCase
{
    public function testGetYears(): void
    {
        $this->assertEquals(
            YearCollection::fromArray([
                Year::fromInt(2000), Year::fromInt(2001), Year::fromInt(2002), Year::fromInt(2003),
                Year::fromInt(2004), Year::fromInt(2005), Year::fromInt(2006), Year::fromInt(2007),
                Year::fromInt(2008), Year::fromInt(2009), Year::fromInt(2010), Year::fromInt(2011),
                Year::fromInt(2012), Year::fromInt(2013), Year::fromInt(2014), Year::fromInt(2015),
                Year::fromInt(2016), Year::fromInt(2017), Year::fromInt(2018), Year::fromInt(2019),
                Year::fromInt(2020), Year::fromInt(2021), Year::fromInt(2022), Year::fromInt(2023)]),
            (new StravaYears(
                PausedClock::on(SerializableDateTime::fromString('2023-10-31'))
            ))->getYears(),
        );
    }
}
