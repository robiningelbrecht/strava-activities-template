<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Ftp\FtpValue;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ActivityTest extends TestCase
{
    #[DataProvider(methodName: 'provideDataAthleteAgeData')]
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

    public function testGetIntensityWithFtp(): void
    {
        $activityWithFtp = ActivityBuilder::fromDefaults()
            ->withData([
                'average_watts' => 250,
                'moving_time' => 3600,
            ])
            ->build();
        $activityWithFtp->enrichWithFtp(FtpValue::fromInt(250));
        $activityWithFtp->updateHasDetailedPowerData(true);

        $this->assertEquals(
            100,
            $activityWithFtp->getIntensity(),
        );
    }

    public function testGetIntensityWithHeartRate(): void
    {
        $activityWithFtp = ActivityBuilder::fromDefaults()
            ->withData([
                'average_heartrate' => 171,
                'moving_time' => 3600,
            ])
            ->build();
        $activityWithFtp->enrichWithAthleteBirthday(SerializableDateTime::fromString('1989-08-14'));

        $this->assertEquals(
            100,
            $activityWithFtp->getIntensity(),
        );
    }

    public function testGetIntensityShouldBeNull(): void
    {
        $activityWithFtp = ActivityBuilder::fromDefaults()
            ->withData([
                'moving_time' => 3600,
            ])
            ->build();

        $this->assertNull(
            $activityWithFtp->getIntensity(),
        );
    }

    public static function provideDataAthleteAgeData(): array
    {
        return [
            [SerializableDateTime::fromString('2023-08-13'), SerializableDateTime::fromString('1989-08-14'), 33],
            [SerializableDateTime::fromString('2023-08-14'), SerializableDateTime::fromString('1989-08-14'), 34],
            [SerializableDateTime::fromString('2023-08-15'), SerializableDateTime::fromString('1989-08-14'), 34],
        ];
    }
}
