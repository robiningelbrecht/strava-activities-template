<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CalculateBestStreamAverages;

use App\Domain\Strava\Activity\Stream\ReadModel\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\ReadModel\ActivityStreamDetailsRepository;
use App\Domain\Strava\Activity\Stream\WriteModel\ActivityStreamRepository;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;

#[AsCommandHandler]
final readonly class CalculateBestStreamAveragesCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityStreamDetailsRepository $activityStreamDetailsRepository,
        private ActivityStreamRepository $activityStreamRepository,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof CalculateBestStreamAverages);
        $command->getOutput()->writeln('Calculating best stream averages...');

        $streams = $this->activityStreamDetailsRepository->findWithoutBestAverages();
        /** @var \App\Domain\Strava\Activity\Stream\ActivityStream $stream */
        foreach ($streams as $stream) {
            $bestAverages = [];
            foreach (ActivityPowerRepository::TIME_INTERVAL_IN_SECONDS_OVERALL as $timeIntervalInSeconds) {
                if (!$bestAverage = $stream->calculateBestAverageForTimeInterval($timeIntervalInSeconds)) {
                    continue;
                }
                $bestAverages[$timeIntervalInSeconds] = $bestAverage;
            }
            $stream->updateBestAverages($bestAverages);
            $this->activityStreamRepository->update($stream);
        }
    }
}
