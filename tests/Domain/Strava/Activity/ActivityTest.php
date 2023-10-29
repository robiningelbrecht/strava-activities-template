<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ActivityTest extends TestCase
{
    #[DataProvider(methodName: 'provideData')]
    public function testGetAthleteAge(
        SerializableDateTime $activityStartDate,
        SerializableDateTime $athleteBirthday,
        int $expectedAge): void
    {
        $activity = ActivityBuilder::fromDefaults()
            ->withStartDateTime($activityStartDate)
            ->build();
        $activity->enrichWithAthleteBirthday($athleteBirthday);

        $this->assertEquals(
            $expectedAge,
            $activity->getAthleteAgeInYears()
        );
    }

    public static function provideData(): array
    {
        return [
            [SerializableDateTime::fromString('2023-08-13'), SerializableDateTime::fromString('1989-08-14'), 33],
            [SerializableDateTime::fromString('2023-08-14'), SerializableDateTime::fromString('1989-08-14'), 34],
            [SerializableDateTime::fromString('2023-08-15'), SerializableDateTime::fromString('1989-08-14'), 34],
        ];
    }
}
