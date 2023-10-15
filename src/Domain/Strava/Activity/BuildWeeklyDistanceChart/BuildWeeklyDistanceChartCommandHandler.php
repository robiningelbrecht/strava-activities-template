<?php

namespace App\Domain\Strava\Activity\BuildWeeklyDistanceChart;

use App\Domain\Strava\Activity\StravaActivityRepository;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Lcobucci\Clock\Clock;
use League\Flysystem\Filesystem;

#[AsCommandHandler]
final readonly class BuildWeeklyDistanceChartCommandHandler implements CommandHandler
{
    public function __construct(
        private StravaActivityRepository $stravaActivityRepository,
        private Filesystem $filesystem,
        private Clock $clock,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildWeeklyDistanceChart);

        $allActivities = $this->stravaActivityRepository->findAll();
        $this->filesystem->write(
            'build/chart-1000-300.json',
            Json::encode(
                WeeklyDistanceChartBuilder::fromActivities(
                    activities: $allActivities,
                    now: SerializableDateTime::fromDateTimeImmutable($this->clock->now()),
                )->build(),
                JSON_PRETTY_PRINT
            ),
        );
    }
}
