<?php

namespace App\Domain\Strava\Activity\BuildEddingtonChart;

use App\Domain\Strava\Activity\StravaActivityRepository;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Serialization\Json;
use League\Flysystem\Filesystem;

#[AsCommandHandler]
final readonly class BuildEddingtonChartCommandHandler implements CommandHandler
{
    public function __construct(
        private StravaActivityRepository $stravaActivityRepository,
        private Filesystem $filesystem,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildEddingtonChart);

        $eddington = Eddington::fromActivities($this->stravaActivityRepository->findAll());

        $this->filesystem->write(
            'build/chart-activities-eddington.json',
            Json::encode(
                EddingtonChartBuilder::fromEddington($eddington)->build(),
                JSON_PRETTY_PRINT
            ),
        );
    }
}
