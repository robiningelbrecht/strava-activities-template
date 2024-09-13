<?php

namespace App\Domain\Strava\Activity\Stream\ReadModel;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Domain\Strava\Activity\Stream\PowerOutput;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypes;
use App\Infrastructure\Exception\EntityNotFound;
use Carbon\CarbonInterval;

final class StreamBasedActivityPowerRepository implements ActivityPowerRepository
{
    /** @var array<mixed> */
    private static array $cachedPowerOutputs = [];

    public function __construct(
        private readonly ActivityDetailsRepository $activityDetailsRepository,
        private readonly ActivityStreamDetailsRepository $activityStreamDetailsRepository,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function findBestForActivity(ActivityId $activityId): array
    {
        if (array_key_exists((string) $activityId, StreamBasedActivityPowerRepository::$cachedPowerOutputs)) {
            return StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activityId];
        }

        $activities = $this->activityDetailsRepository->findAll();
        $powerStreams = $this->activityStreamDetailsRepository->findByStreamType(StreamType::WATTS);

        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($activities as $activity) {
            StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activity->getId()] = [];
            $powerStreamsForActivity = $powerStreams->filter(fn (ActivityStream $stream) => $stream->getActivityId() == $activity->getId());

            if ($powerStreamsForActivity->isEmpty()) {
                continue;
            }

            /** @var ActivityStream $activityStream */
            $activityStream = $powerStreamsForActivity->getFirst();
            $bestAverages = $activityStream->getBestAverages();

            foreach (self::TIME_INTERVAL_IN_SECONDS as $timeIntervalInSeconds) {
                $interval = CarbonInterval::seconds($timeIntervalInSeconds);
                if (!isset($bestAverages[$timeIntervalInSeconds])) {
                    continue;
                }
                $bestAverageForTimeInterval = $bestAverages[$timeIntervalInSeconds];
                StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activity->getId()][$timeIntervalInSeconds] = PowerOutput::fromState(
                    time: (int) $interval->totalHours ? $interval->totalHours.' h' : ((int) $interval->totalMinutes ? $interval->totalMinutes.' m' : $interval->totalSeconds.' s'),
                    power: $bestAverageForTimeInterval,
                    relativePower: round($bestAverageForTimeInterval / $activity->getAthleteWeight()->getFloat(), 2),
                );
            }
        }

        return StreamBasedActivityPowerRepository::$cachedPowerOutputs[(string) $activityId];
    }

    /**
     * @return array<int, int>
     */
    public function findTimeInSecondsPerWattageForActivity(ActivityId $activityId): array
    {
        if (!$this->activityStreamDetailsRepository->hasOneForActivityAndStreamType(
            activityId: $activityId,
            streamType: StreamType::WATTS
        )) {
            return [];
        }

        $streams = $this->activityStreamDetailsRepository->findByActivityAndStreamTypes(
            activityId: $activityId,
            streamTypes: StreamTypes::fromArray([StreamType::WATTS])
        );
        /** @var ActivityStream $stream */
        $stream = $streams->getByStreamType(StreamType::WATTS);
        $powerStreamForActivity = array_count_values(array_filter($stream->getData(), fn (mixed $item) => !is_null($item)));
        ksort($powerStreamForActivity);

        return $powerStreamForActivity;
    }

    /**
     * @return PowerOutput[]
     */
    public function findBest(): array
    {
        /** @var PowerOutput[] $best */
        $best = [];

        foreach (self::TIME_INTERVAL_IN_SECONDS_OVERALL as $timeIntervalInSeconds) {
            try {
                $stream = $this->activityStreamDetailsRepository->findWithBestAverageFor(
                    intervalInSeconds: $timeIntervalInSeconds,
                    streamType: StreamType::WATTS
                );
            } catch (EntityNotFound) {
                continue;
            }

            $activity = $this->activityDetailsRepository->find($stream->getActivityId());
            $interval = CarbonInterval::seconds($timeIntervalInSeconds);
            $bestAverageForTimeInterval = $stream->getBestAverages()[$timeIntervalInSeconds];

            $best[$timeIntervalInSeconds] = PowerOutput::fromState(
                time: (int) $interval->totalHours ? $interval->totalHours.' h' : ((int) $interval->totalMinutes ? $interval->totalMinutes.' m' : $interval->totalSeconds.' s'),
                power: $bestAverageForTimeInterval,
                relativePower: round($bestAverageForTimeInterval / $activity->getAthleteWeight()->getFloat(), 2),
                activity: $activity,
            );
        }

        return $best;
    }
}
