<?php

namespace App\Domain\Strava\Activity\Stream\ReadModel;

use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Domain\Strava\Activity\Stream\PowerOutput;
use App\Domain\Strava\Activity\Stream\PowerStream;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypeCollection;
use Carbon\CarbonInterval;

final class StreamBasedActivityPowerRepository implements ActivityPowerRepository
{
    /** @var array<mixed> */
    private static array $cachedPowerOutputs = [];

    public function __construct(
        private readonly ActivityDetailsRepository $activityDetailsRepository,
        private readonly ActivityStreamDetailsRepository $activityStreamDetailsRepository
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

        $activities = $this->activityDetailsRepository->findAll();
        $powerStreams = $this->activityStreamDetailsRepository->findByStreamType(StreamType::WATTS);

        foreach ($activities as $activity) {
            StreamBasedActivityPowerRepository::$cachedPowerOutputs[$activity->getId()] = [];
            $powerStreamsForActivity = $powerStreams->filter(fn (ActivityStream $stream) => $stream->getActivityId() == $activity->getId());

            if ($powerStreamsForActivity->isEmpty()) {
                continue;
            }

            /** @var \App\Domain\Strava\Activity\Stream\ActivityStream $activityStream */
            $activityStream = $powerStreamsForActivity->getFirst();
            $stream = PowerStream::fromStream($activityStream);
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
        if (!$this->activityStreamDetailsRepository->hasOneForActivityAndStreamType(
            activityId: $activityId,
            streamType: StreamType::WATTS
        )) {
            return [];
        }

        $streams = $this->activityStreamDetailsRepository->findByActivityAndStreamTypes(
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
     * @return PowerOutput[]
     */
    public function findBest(): array
    {
        /** @var \App\Domain\Strava\Activity\Stream\PowerStream[] $powerStreams */
        $powerStreams = $this->activityStreamDetailsRepository->findByStreamType(StreamType::WATTS)->map(
            fn (ActivityStream $stream) => PowerStream::fromStream($stream),
        );

        /** @var PowerOutput[] $best */
        $best = [];

        foreach ($powerStreams as $stream) {
            foreach (self::TIME_INTERVAL_IN_SECONDS as $timeIntervalInSeconds) {
                if (!$power = $stream->getBestAverageForTimeInterval($timeIntervalInSeconds)) {
                    continue;
                }
                if (!isset($best[$timeIntervalInSeconds]) || $best[$timeIntervalInSeconds]->getPower() < $power) {
                    $activity = $this->activityDetailsRepository->find($stream->getActivityId());
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
