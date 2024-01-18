<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CalculateBestStreamAverages;

use App\Domain\Strava\Activity\Stream\ReadModel\ActivityStreamDetailsRepository;
use App\Domain\Strava\Activity\Stream\WriteModel\ActivityStreamRepository;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;

#[AsCommandHandler]
class CalculateBestStreamAveragesCommandHandler implements CommandHandler
{
    public const AVERAGES_TO_CALCULATE = [1, 5, 10, 15, 30, 45, 60, 120, 180, 240, 300, 390, 480, 720, 960, 1200, 1800, 2400, 3000, 3600];

    public function __construct(
        private readonly ActivityStreamDetailsRepository $activityStreamDetailsRepository,
        private readonly ActivityStreamRepository $activityStreamRepository
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
            foreach (self::AVERAGES_TO_CALCULATE as $timeIntervalInSeconds) {
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
