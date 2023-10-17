<?php

namespace App\Domain\Strava\Activity\BuildActivityHeatmapChart;

use App\Domain\Strava\Activity\StravaActivityRepository;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Lcobucci\Clock\Clock;
use League\Flysystem\Filesystem;

#[AsCommandHandler]
final readonly class BuildActivityHeatmapChartCommandHandler implements CommandHandler
{
    public function __construct(
        private StravaActivityRepository $stravaActivityRepository,
        private Filesystem $filesystem,
        private Clock $clock
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildActivityHeatmapChart);

        $this->filesystem->write(
            'build/charts/chart-activities-heatmap_1000_180.json',
            Json::encode(
                [
                    'width' => 1000,
                    'height' => 180,
                    'options' => ActivityHeatmapChartBuilder::fromActivities(
                        activities: $this->stravaActivityRepository->findAll(),
                        now: SerializableDateTime::fromDateTimeImmutable($this->clock->now()),
                    )->build(),
                ],
                JSON_PRETTY_PRINT),
        );
    }
}
