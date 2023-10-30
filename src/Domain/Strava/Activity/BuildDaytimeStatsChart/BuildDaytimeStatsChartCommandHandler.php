<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BuildDaytimeStatsChart;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Serialization\Json;
use League\Flysystem\FilesystemOperator;

#[AsCommandHandler]
final readonly class BuildDaytimeStatsChartCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private FilesystemOperator $filesystem
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildDaytimeStatsChart);

        $this->filesystem->write(
            'build/charts/chart-daytime-stats.json',
            Json::encode(
                [
                    'width' => 1000,
                    'height' => 300,
                    'options' => DaytimeStatsChartsBuilder::fromDaytimeStats(
                        DaytimeStats::fromActivities($this->activityRepository->findAll()),
                    )->build(),
                ],
                JSON_PRETTY_PRINT
            ),
        );
    }
}
