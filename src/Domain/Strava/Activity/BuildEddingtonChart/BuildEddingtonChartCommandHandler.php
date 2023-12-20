<?php

namespace App\Domain\Strava\Activity\BuildEddingtonChart;

use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Serialization\Json;
use League\Flysystem\FilesystemOperator;

#[AsCommandHandler]
final readonly class BuildEddingtonChartCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityDetailsRepository $activityDetailsRepository,
        private FilesystemOperator $filesystem,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildEddingtonChart);

        $eddington = Eddington::fromActivities($this->activityDetailsRepository->findAll());

        $this->filesystem->write(
            'build/charts/chart-activities-eddington_1000_300.json',
            Json::encode(
                [
                    'width' => 1000,
                    'height' => 300,
                    'options' => EddingtonChartBuilder::fromEddington($eddington)
                        ->withoutTooltip()
                        ->build(),
                ],
                JSON_PRETTY_PRINT
            ),
        );
    }
}
