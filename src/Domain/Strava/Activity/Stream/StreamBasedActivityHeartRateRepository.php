<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Athlete\HeartRateZone;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

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

    public function findTimeInSecondsInHeartRateZoneForActivity(int $activityId, HeartRateZone $heartRateZone): int
    {
        $cachedHeartRateZones = $this->getCachedHeartRateZones();

        return $cachedHeartRateZones[$activityId][$heartRateZone->value];
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
        $heartRateStreams = $this->activityStreamRepository->findByStreamType(StreamType::HEART_RATE)->toArray();
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
            $heartRateStreamsForActivity = array_filter($heartRateStreams, fn (ActivityStream $stream) => $stream->getActivityId() == $activity->getId());

            if (!$heartRateStreamsForActivity) {
                continue;
            }

            $activity->enrichWithAthleteBirthday($athleteBirthday);
            if (!$athleteMaxHeartRate = $activity->getAthleteMaxHeartRate()) {
                continue;
            }

            /** @var \App\Domain\Strava\Activity\Stream\ActivityStream $stream */
            $stream = reset($heartRateStreamsForActivity);
            foreach (HeartRateZone::cases() as $heartRateZone) {
                [$minHeartRate, $maxHeartRate] = $heartRateZone->getMinMaxRange($athleteMaxHeartRate);
                $secondsInZone = count(array_filter($stream->getData(), fn (int $heartRate) => $heartRate >= $minHeartRate && $heartRate <= $maxHeartRate));
                StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivity[$activity->getId()][$heartRateZone->value] = $secondsInZone;
            }
        }

        return StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivity;
    }
}
