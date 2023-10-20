<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\StravaActivityRepository;
use App\Domain\Strava\PowerOutput;
use Carbon\CarbonInterval;

final class StravaActivityPowerRepository
{
    public const TIME_INTERVAL_IN_SECONDS = [5, 10, 30, 60, 300, 480, 1200, 3600];

    /** @var array<mixed> */
    private static array $cachedPowerOutputs = [];

    public function __construct(
        private readonly StravaActivityRepository $stravaActivityRepository,
        private readonly StravaActivityStreamRepository $stravaActivityStreamRepository
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function findBestForActivity(int $activityId): array
    {
        if (array_key_exists($activityId, StravaActivityPowerRepository::$cachedPowerOutputs)) {
            return StravaActivityPowerRepository::$cachedPowerOutputs[$activityId];
        }

        $activities = $this->stravaActivityRepository->findAll();
        $powerStreams = $this->stravaActivityStreamRepository->findByStreamType(StreamType::WATTS)->toArray();

        foreach ($activities as $activity) {
            StravaActivityPowerRepository::$cachedPowerOutputs[$activity->getId()] = [];
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
                StravaActivityPowerRepository::$cachedPowerOutputs[$activity->getId()][$timeIntervalInSeconds] = PowerOutput::fromState(
                    time: (int) $interval->totalHours ? $interval->totalHours.' h' : ((int) $interval->totalMinutes ? $interval->totalMinutes.' m' : $interval->totalSeconds.' s'),
                    power: $bestAverageForTimeInterval,
                    relativePower: $bestRelativeAverageForTimeInterval,
                );
            }
        }

        return StravaActivityPowerRepository::$cachedPowerOutputs[$activityId];
    }

    /**
     * @return array<mixed>
     */
    public function findBest(): array
    {
        /** @var \App\Domain\Strava\Activity\Stream\PowerStream[] $powerStreams */
        $powerStreams = array_map(
            fn (ActivityStream $stream) => PowerStream::fromStream($stream),
            $this->stravaActivityStreamRepository->findByStreamType(StreamType::WATTS)->toArray()
        );

        /** @var PowerOutput[] $best */
        $best = [];

        foreach ($powerStreams as $stream) {
            foreach (self::TIME_INTERVAL_IN_SECONDS as $timeIntervalInSeconds) {
                if (!$power = $stream->getBestAverageForTimeInterval($timeIntervalInSeconds)) {
                    continue;
                }
                if (!isset($best[$timeIntervalInSeconds]) || $best[$timeIntervalInSeconds]->getPower() < $power) {
                    $activity = $this->stravaActivityRepository->find($stream->getActivityId());
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
