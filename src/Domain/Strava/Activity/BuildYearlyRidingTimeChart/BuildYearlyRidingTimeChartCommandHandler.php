<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BuildYearlyRidingTimeChart;

use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Lcobucci\Clock\Clock;
use League\Flysystem\FilesystemOperator;

#[AsCommandHandler]
final readonly class BuildYearlyRidingTimeChartCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityDetailsRepository $activityDetailsRepository,
        private FilesystemOperator $filesystem,
        private Clock $clock,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildYearlyRidingTimeChart);

        $this->filesystem->write(
            'build/charts/chart-yearly-riding-stats.json',
            Json::encode(
                [
                    'width' => 1000,
                    'height' => 400,
                    'options' => YearlyRidingTimeChartBuilder::fromActivities(
                        activities: $this->activityDetailsRepository->findAll(),
                        now: SerializableDateTime::fromDateTimeImmutable($this->clock->now()),
                    )->build(),
                ],
                JSON_PRETTY_PRINT
            ),
        );
    }
}
