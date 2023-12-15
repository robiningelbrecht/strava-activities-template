<?php

namespace App\Domain\Strava\Activity\BuildEddingtonChart;

use App\Domain\Strava\Activity\ActivityCollection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class Eddington
{
    private const DATE_FORMAT = 'Y-m-d';
    /** @var array<string, int|float> */
    private static array $distancesPerDay = [];

    private function __construct(
        private readonly ActivityCollection $activities,
    ) {
    }

    /**
     * @return array<string, float|int>
     */
    private function getDistancesPerDay(): array
    {
        if (!empty(Eddington::$distancesPerDay)) {
            return Eddington::$distancesPerDay;
        }

        Eddington::$distancesPerDay = [];
        foreach ($this->activities as $activity) {
            /** @var string $day */
            $day = $activity->getStartDate()->format(self::DATE_FORMAT);
            if (!array_key_exists($day, Eddington::$distancesPerDay)) {
                Eddington::$distancesPerDay[$day] = 0;
            }
            Eddington::$distancesPerDay[$day] += $activity->getDistanceInKilometer();
        }

        return Eddington::$distancesPerDay;
    }

    public function getLongestDistanceInADay(): int
    {
        if (empty($this->getDistancesPerDay())) {
            return 0;
        }

        return (int) round(max($this->getDistancesPerDay()));
    }

    /**
     * @return array<mixed>
     */
    public function getTimesCompletedData(): array
    {
        $data = [];
        for ($distance = 1; $distance <= $this->getLongestDistanceInADay(); ++$distance) {
            // We need to count the number of days we exceeded this distance.
            $data[$distance] = count(array_filter($this->getDistancesPerDay(), fn (float $distanceForDay) => $distanceForDay >= $distance));
        }

        return $data;
    }

    public function getNumber(): int
    {
        $number = 1;
        for ($distance = 1; $distance <= $this->getLongestDistanceInADay(); ++$distance) {
            $timesCompleted = count(array_filter($this->getDistancesPerDay(), fn (float $distanceForDay) => $distanceForDay >= $distance));
            if ($timesCompleted < $distance) {
                break;
            }
            $number = $distance;
        }

        return $number;
    }

    /**
     * @return array<int, int>
     */
    public function getRidesToCompleteForFutureNumbers(): array
    {
        $futureNumbers = [];
        $eddingtonNumber = $this->getNumber();
        $timesCompleted = $this->getTimesCompletedData();
        for ($distance = $eddingtonNumber + 1; $distance <= $this->getLongestDistanceInADay(); ++$distance) {
            $futureNumbers[$distance] = $distance - $timesCompleted[$distance];
        }

        return $futureNumbers;
    }

    /**
     * @return array<int, SerializableDateTime>
     */
    public function getEddingtonHistory(): array
    {
        $history = [];
        $eddingtonNumber = $this->getNumber();
        // We need the distances sorted by oldest => newest.
        $distancesPerDay = array_reverse($this->getDistancesPerDay());

        for ($distance = $eddingtonNumber; $distance > 0; --$distance) {
            $countForDistance = 0;
            foreach ($distancesPerDay as $day => $distanceInDay) {
                if ($distanceInDay >= $distance) {
                    ++$countForDistance;
                }
                if ($countForDistance === $distance) {
                    // This is the day we reached the eddington Number.
                    $history[$distance] = SerializableDateTime::fromString($day);
                    break;
                }
            }
        }

        return $history;
    }

    public static function fromActivities(ActivityCollection $activities): self
    {
        return new self($activities);
    }
}
