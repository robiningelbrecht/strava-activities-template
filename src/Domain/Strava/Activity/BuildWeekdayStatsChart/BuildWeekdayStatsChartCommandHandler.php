<?php

namespace App\Domain\Strava\Activity\BuildWeekdayStatsChart;

use App\Domain\Strava\Activity\StravaActivityRepository;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Serialization\Json;
use League\Flysystem\Filesystem;

#[AsCommandHandler]
final readonly class BuildWeekdayStatsChartCommandHandler implements CommandHandler
{
    public function __construct(
        private StravaActivityRepository $stravaActivityRepository,
        private Filesystem $filesystem
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildWeekdayStatsChart);

        $this->filesystem->write(
            'build/charts/chart-weekday-stats_1000_300.json',
            Json::encode(
                WeekdayStatsChartsBuilder::fromActivities(
                    $this->stravaActivityRepository->findAll(),
                )
                ->build(),
                JSON_PRETTY_PRINT
            ),
        );
    }
}
