<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Athlete\HeartRateZone;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Carbon\CarbonInterval;

final class StreamBasedActivityHeartRateRepository implements ActivityHeartRateRepository
{
    /** @var array<mixed> */
    private static array $cachedHeartRateZonesPerActivity = [];

    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ActivityStreamRepository $activityStreamRepository,
        private readonly KeyValueStore $keyValueStore,
    ) {
    }

    public function findTotalTimeInSecondsInHeartRateZone(HeartRateZone $heartRateZone): int
    {
        $cachedHeartRateZones = $this->getCachedHeartRateZones();

        return array_sum(array_map(fn (array $heartRateZones) => $heartRateZones[$heartRateZone->value], $cachedHeartRateZones));
    }

    /**
     * @return HeartRate[]
     */
    public function findHighest(): array
    {
        $heartRateStreams = $this->activityStreamRepository->findByStreamType(StreamType::HEART_RATE);

        /** @var HeartRate[] $best */
        $best = [];

        /** @var \App\Domain\Strava\Activity\Stream\ActivityStream $stream */
        foreach ($heartRateStreams as $stream) {
            foreach (self::TIME_INTERVAL_IN_SECONDS as $timeIntervalInSeconds) {
                if (!$heartRate = $stream->getBestAverageForTimeInterval($timeIntervalInSeconds)) {
                    continue;
                }
                if (!isset($best[$timeIntervalInSeconds]) || $best[$timeIntervalInSeconds]->getRate() < $heartRate) {
                    $activity = $this->activityRepository->find($stream->getActivityId());
                    $interval = CarbonInterval::seconds($timeIntervalInSeconds);

                    $best[$timeIntervalInSeconds] = HeartRate::fromState(
                        time: (int) $interval->totalHours ? $interval->totalHours.' h' : ((int) $interval->totalMinutes ? $interval->totalMinutes.' m' : $interval->totalSeconds.' s'),
                        rate: $heartRate,
                        activity: $activity,
                    );
                }
            }
        }

        return $best;
    }

    /**
     * @return array<int, int>
     */
    public function findTimeInSecondsPerHeartRateForActivity(int $activityId): array
    {
        if (!$this->activityStreamRepository->hasOneForActivityAndStreamType(
            activityId: $activityId,
            streamType: StreamType::HEART_RATE
        )) {
            return [];
        }

        $streams = $this->activityStreamRepository->findByActivityAndStreamTypes(
            activityId: $activityId,
            streamTypes: StreamTypeCollection::fromArray([StreamType::HEART_RATE])
        );
        /** @var \App\Domain\Strava\Activity\Stream\ActivityStream $stream */
        $stream = $streams->getByStreamType(StreamType::HEART_RATE);
        $heartRateStreamForActivity = array_count_values($stream->getData());
        ksort($heartRateStreamForActivity);

        return $heartRateStreamForActivity;
    }

    /**
     * @return array<mixed>
     */
    private function getCachedHeartRateZones(): array
    {
        if (!empty(StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivity)) {
            return StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivity;
        }

        $activities = $this->activityRepository->findAll();
        $heartRateStreams = $this->activityStreamRepository->findByStreamType(StreamType::HEART_RATE);
        $athleteBirthday = SerializableDateTime::fromString((string) $this->keyValueStore->find(Key::ATHLETE_BIRTHDAY)->getValue());

        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($activities as $activity) {
            StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivity[$activity->getId()] = [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
            ];
            $heartRateStreamsForActivity = $heartRateStreams->filter(fn (ActivityStream $stream) => $stream->getActivityId() == $activity->getId());

            if ($heartRateStreamsForActivity->isEmpty()) {
                continue;
            }

            $activity->enrichWithAthleteBirthday($athleteBirthday);
            if (!$athleteMaxHeartRate = $activity->getAthleteMaxHeartRate()) {
                continue;
            }

            /** @var \App\Domain\Strava\Activity\Stream\ActivityStream $stream */
            $stream = $heartRateStreamsForActivity->getFirst();
            foreach (HeartRateZone::cases() as $heartRateZone) {
                [$minHeartRate, $maxHeartRate] = $heartRateZone->getMinMaxRange($athleteMaxHeartRate);
                $secondsInZone = count(array_filter($stream->getData(), fn (int $heartRate) => $heartRate >= $minHeartRate && $heartRate <= $maxHeartRate));
                StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivity[$activity->getId()][$heartRateZone->value] = $secondsInZone;
            }
        }

        return StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivity;
    }
}
