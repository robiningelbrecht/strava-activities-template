<?php

namespace App\Domain\Strava\Activity\BuildWeeklyDistanceChart;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Lcobucci\Clock\Clock;
use League\Flysystem\FilesystemOperator;

#[AsCommandHandler]
final readonly class BuildWeeklyDistanceChartCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private FilesystemOperator $filesystem,
        private Clock $clock,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildWeeklyDistanceChart);

        $this->filesystem->write(
            'build/charts/chart_1000_300.json',
            Json::encode(
                [
                    'width' => 1000,
                    'height' => 300,
                    'options' => WeeklyDistanceChartBuilder::fromActivities(
                        activities: $this->activityRepository->findAll(),
                        now: SerializableDateTime::fromDateTimeImmutable($this->clock->now()),
                    )->build(),
                ],
                JSON_PRETTY_PRINT
            ),
        );
    }
}
