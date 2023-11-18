<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\PowerOutput;
use Carbon\CarbonInterval;

final class StreamBasedActivityPowerRepository implements ActivityPowerRepository
{
    /** @var array<mixed> */
    private static array $cachedPowerOutputs = [];

    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ActivityStreamRepository $activityStreamRepository
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function findBestForActivity(int $activityId): array
    {
        if (array_key_exists($activityId, StreamBasedActivityPowerRepository::$cachedPowerOutputs)) {
            return StreamBasedActivityPowerRepository::$cachedPowerOutputs[$activityId];
        }

        $activities = $this->activityRepository->findAll();
        $powerStreams = $this->activityStreamRepository->findByStreamType(StreamType::WATTS)->toArray();

        foreach ($activities as $activity) {
            StreamBasedActivityPowerRepository::$cachedPowerOutputs[$activity->getId()] = [];
            $powerStreamsForActivity = array_filter($powerStreams, fn (ActivityStream $stream) => $stream->getActivityId() == $activity->getId());

            if (!$powerStreamsForActivity) {
                continue;
            }

            $stream = PowerStream::fromStream(reset($powerStreamsForActivity));
            foreach (self::TIME_INTERVAL_IN_SECONDS as $timeIntervalInSeconds) {
                $interval = CarbonInterval::seconds($timeIntervalInSeconds);
                if (!$bestAverageForTimeInterval = $stream->getBestAverageForTimeInterval($timeIntervalInSeconds)) {
                    continue;
                }
                if (!$bestRelativeAverageForTimeInterval = $stream->getBestRelativeAverageForTimeInterval($timeIntervalInSeconds, $activity->getAthleteWeight())) {
                    continue;
                }
                StreamBasedActivityPowerRepository::$cachedPowerOutputs[$activity->getId()][$timeIntervalInSeconds] = PowerOutput::fromState(
                    time: (int) $interval->totalHours ? $interval->totalHours.' h' : ((int) $interval->totalMinutes ? $interval->totalMinutes.' m' : $interval->totalSeconds.' s'),
                    power: $bestAverageForTimeInterval,
                    relativePower: $bestRelativeAverageForTimeInterval,
                );
            }
        }

        return StreamBasedActivityPowerRepository::$cachedPowerOutputs[$activityId];
    }

    /**
     * @return array<int, int>
     */
    public function findTimeInSecondsPerWattageForActivity(int $activityId): array
    {
        if (!$this->activityStreamRepository->hasOneForActivityAndStreamType(
            activityId: $activityId,
            streamType: StreamType::WATTS
        )) {
            return [];
        }

        $streams = $this->activityStreamRepository->findByActivityAndStreamTypes(
            activityId: $activityId,
            streamTypes: StreamTypeCollection::fromArray([StreamType::WATTS])
        );
        /** @var \App\Domain\Strava\Activity\Stream\ActivityStream $stream */
        $stream = $streams->getByStreamType(StreamType::WATTS);
        $powerStreamForActivity = array_count_values($stream->getData());
        ksort($powerStreamForActivity);

        return $powerStreamForActivity;
    }

    /**
     * @return array<mixed>
     */
    public function findBest(): array
    {
        /** @var \App\Domain\Strava\Activity\Stream\PowerStream[] $powerStreams */
        $powerStreams = array_map(
            fn (ActivityStream $stream) => PowerStream::fromStream($stream),
            $this->activityStreamRepository->findByStreamType(StreamType::WATTS)->toArray()
        );

        /** @var PowerOutput[] $best */
        $best = [];

        foreach ($powerStreams as $stream) {
            foreach (self::TIME_INTERVAL_IN_SECONDS as $timeIntervalInSeconds) {
                if (!$power = $stream->getBestAverageForTimeInterval($timeIntervalInSeconds)) {
                    continue;
                }
                if (!isset($best[$timeIntervalInSeconds]) || $best[$timeIntervalInSeconds]->getPower() < $power) {
                    $activity = $this->activityRepository->find($stream->getActivityId());
                    $interval = CarbonInterval::seconds($timeIntervalInSeconds);

                    if (!$bestRelativeAverageForTimeInterval = $stream->getBestRelativeAverageForTimeInterval($timeIntervalInSeconds, $activity->getAthleteWeight())) {
                        continue;
                    }

                    $best[$timeIntervalInSeconds] = PowerOutput::fromState(
                        time: (int) $interval->totalHours ? $interval->totalHours.' h' : ((int) $interval->totalMinutes ? $interval->totalMinutes.' m' : $interval->totalSeconds.' s'),
                        power: $power,
                        relativePower: $bestRelativeAverageForTimeInterval,
                        activity: $activity,
                    );
                }
            }
        }

        return $best;
    }
}
