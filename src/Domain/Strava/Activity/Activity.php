<?php

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Ftp\FtpValue;
use App\Domain\Strava\PowerOutput;
use App\Domain\Weather\OpenMeteo\Weather;
use App\Infrastructure\Time\TimeFormatter;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Weight;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class Activity
{
    use TimeFormatter;

    public const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s\Z';
    private ?string $gearName = null;
    /** @var array<mixed> */
    private array $bestPowerOutputs = [];
    private ?FtpValue $ftp = null;
    private ?SerializableDateTime $athleteBirthday = null;
    private bool $hasDetailedPowerData = false;

    /**
     * @param array<mixed> $data
     * @param array<mixed> $weather
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly int $activityId,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $startDateTime,
        #[ORM\Column(type: 'json')]
        private array $data,
        #[ORM\Column(type: 'json', nullable: true)]
        private array $weather = [],
        #[ORM\Column(type: 'string', nullable: true)]
        private ?string $gearId = null,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function create(
        int $activityId,
        SerializableDateTime $startDateTime,
        array $data,
        string $gearId = null,
    ): self {
        return new self(
            activityId: $activityId,
            startDateTime: $startDateTime,
            data: $data,
            gearId: $gearId
        );
    }

    /**
     * @param array<mixed> $data
     * @param array<mixed> $weather
     */
    public static function fromState(
        int $activityId,
        SerializableDateTime $startDateTime,
        array $data,
        array $weather = [],
        string $gearId = null,
    ): self {
        return new self(
            activityId: $activityId,
            startDateTime: $startDateTime,
            data: $data,
            weather: $weather,
            gearId: $gearId
        );
    }

    public function getId(): int
    {
        return $this->activityId;
    }

    public function getStartDate(): SerializableDateTime
    {
        return $this->startDateTime;
    }

    public function getType(): ActivityType
    {
        return ActivityType::from($this->data['type']);
    }

    public function getLatitude(): ?Latitude
    {
        return Latitude::fromOptionalString($this->data['start_latlng'][0] ?? null);
    }

    public function getLongitude(): ?Longitude
    {
        return Longitude::fromOptionalString($this->data['start_latlng'][1] ?? null);
    }

    public function getKudoCount(): int
    {
        return $this->data['kudos_count'] ?? 0;
    }

    public function updateKudoCount(int $count): void
    {
        $this->data['kudos_count'] = $count;
    }

    public function getGearId(): ?string
    {
        return $this->gearId;
    }

    public function updateGearId(string $gearId = null): void
    {
        $this->gearId = $gearId;
    }

    public function getGearName(): ?string
    {
        return $this->gearName;
    }

    public function enrichWithGearName(string $gearName): void
    {
        $this->gearName = $gearName;
    }

    public function getBestAveragePowerForTimeInterval(int $timeInterval): ?PowerOutput
    {
        return $this->bestPowerOutputs[$timeInterval] ?? null;
    }

    /**
     * @param array<mixed> $bestPowerOutputs
     */
    public function enrichWithBestPowerOutputs(array $bestPowerOutputs): void
    {
        $this->bestPowerOutputs = $bestPowerOutputs;
        $this->hasDetailedPowerData = !empty($bestPowerOutputs);
    }

    /**
     * @param array<mixed> $weather
     */
    public function updateWeather(array $weather): void
    {
        $this->weather = $weather;
    }

    /**
     * @return array<mixed>
     */
    public function getAllWeatherData(): array
    {
        return $this->weather;
    }

    public function getWeather(): ?Weather
    {
        $hour = $this->getStartDate()->getHourWithoutLeadingZero();
        if (!empty($this->weather['hourly']['time'][$hour])) {
            // Use weather known for the given hour.
            $weather = [];
            foreach ($this->weather['hourly'] as $metric => $values) {
                $weather[$metric] = $values[$hour];
            }

            return Weather::fromMap($weather);
        }

        if (!empty($this->weather['daily'])) {
            // Use weather known for that day.
            $weather = [];
            foreach ($this->weather['daily'] as $metric => $values) {
                $weather[$metric] = reset($values);
            }

            return Weather::fromMap($weather);
        }

        return null;
    }

    /**
     * @return array<string>
     */
    public function getLocalImagePaths(): array
    {
        return $this->data['localImagePaths'] ?? [];
    }

    /**
     * @return array<string>
     */
    public function getRemoteImagePaths(): array
    {
        return array_map(
            fn (string $path) => 'https://raw.githubusercontent.com/'.$_ENV['REPOSITORY_NAME'].'/master/'.$path,
            $this->getLocalImagePaths()
        );
    }

    public function getTotalImageCount(): int
    {
        return $this->data['total_photo_count'] ?? 0;
    }

    /**
     * @param array<string> $localImagePaths
     */
    public function updateLocalImagePaths(array $localImagePaths): void
    {
        $this->data['localImagePaths'] = $localImagePaths;
    }

    public function getName(): string
    {
        return trim(str_replace('Zwift - ', '', $this->data['name']));
    }

    public function getDistance(): float
    {
        return round($this->data['distance'] / 1000);
    }

    public function getElevation(): int
    {
        return (int) round($this->data['total_elevation_gain']);
    }

    public function getCalories(): int
    {
        return $this->data['calories'] ?? 0;
    }

    public function getAveragePower(): ?int
    {
        if (isset($this->data['average_watts'])) {
            return (int) round($this->data['average_watts']);
        }

        return null;
    }

    public function getMaxPower(): ?int
    {
        if (isset($this->data['max_watts'])) {
            return (int) round($this->data['max_watts']);
        }

        return null;
    }

    public function getAverageSpeedInKmPerH(): float
    {
        return round($this->data['average_speed'] * 3.6, 1);
    }

    public function getMaxSpeedInKmPerH(): float
    {
        return round($this->data['max_speed'] * 3.6, 1);
    }

    public function getAverageHeartRate(): ?int
    {
        if (isset($this->data['average_heartrate'])) {
            return (int) round($this->data['average_heartrate']);
        }

        return null;
    }

    public function getMaxHeartRate(): ?int
    {
        if (isset($this->data['max_heartrate'])) {
            return (int) round($this->data['max_heartrate']);
        }

        return null;
    }

    public function getAverageCadence(): ?int
    {
        return !empty($this->data['average_cadence']) ? (int) round($this->data['average_cadence']) : null;
    }

    public function getMaxCadence(): ?int
    {
        return $this->data['max_cadence'] ?? null;
    }

    public function enrichWithMaxCadence(int $maxCadence): void
    {
        $this->data['max_cadence'] = $maxCadence;
    }

    public function getMovingTime(): int
    {
        return $this->data['moving_time'];
    }

    public function getMovingTimeFormatted(): string
    {
        return $this->formatDurationForHumans($this->getMovingTime());
    }

    public function getUrl(): string
    {
        return 'https://www.strava.com/activities/'.$this->data['id'];
    }

    public function getIntensity(): ?int
    {
        // To calculate intensity, we need
        // 1) Max and average heart rate
        // OR
        // 2) FTP and average power
        if (($ftp = $this->getFtp()) && ($averagePower = $this->getAveragePower()) && $this->hasDetailedPowerData()) {
            // Use more complicated and more accurate calculation.
            // intensityFactor = averagePower / FTP
            // (durationInSeconds * averagePower * intensityFactor) / (FTP x 3600) * 100
            return (int) round(($this->getMovingTime() * $averagePower * ($averagePower / $ftp->getValue())) / ($ftp->getValue() * 3600) * 100);
        }

        if (($averageHeartRate = $this->getAverageHeartRate()) && ($age = $this->getAthleteAgeInYears())) {
            // Use simplified, less accurate calculation.
            // maxHeartRate = = (220 - age) x 0.92
            // intensityFactor = averageHeartRate / maxHeartRate
            // (durationInSeconds x averageHeartRate x intensityFactor) / (maxHeartRate x 3600) x 100
            $maxHeartRate = (220 - $age) * 0.92;

            return (int) round(($this->getMovingTime() * $averageHeartRate * ($averageHeartRate / $maxHeartRate)) / ($maxHeartRate * 3600) * 100);
        }

        return null;
    }

    public function getFtp(): ?FtpValue
    {
        return $this->ftp;
    }

    public function enrichWithFtp(FtpValue $ftp): void
    {
        $this->ftp = $ftp;
    }

    public function getAthleteWeight(): Weight
    {
        return Weight::fromKilograms($this->data['athlete_weight']);
    }

    public function getAthleteAgeInYears(): ?int
    {
        return $this->athleteBirthday?->diff($this->getStartDate())->y;
    }

    public function enrichWithAthleteBirthday(SerializableDateTime $birthday): void
    {
        $this->athleteBirthday = $birthday;
    }

    public function hasDetailedPowerData(): bool
    {
        return $this->hasDetailedPowerData;
    }

    public function updateHasDetailedPowerData(bool $flag): void
    {
        $this->hasDetailedPowerData = $flag;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
