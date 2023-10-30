<?php

namespace App\Domain\Strava\Activity\BuildWeekdayStatsChart;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Serialization\Json;
use League\Flysystem\FilesystemOperator;

#[AsCommandHandler]
final readonly class BuildWeekdayStatsChartCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private FilesystemOperator $filesystem
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildWeekdayStatsChart);

        $this->filesystem->write(
            'build/charts/chart-weekday-stats_1000_300.json',
            Json::encode(
                [
                    'width' => 1000,
                    'height' => 300,
                    'options' => WeekdayStatsChartsBuilder::fromWeekdayStats(
                        WeekdayStats::fromActivities($this->activityRepository->findAll()),
                    )->build(),
                ],
                JSON_PRETTY_PRINT
            ),
        );
    }
}
