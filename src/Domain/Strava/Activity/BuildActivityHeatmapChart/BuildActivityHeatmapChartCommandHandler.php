<?php

namespace App\Domain\Strava\Activity\BuildActivityHeatmapChart;

use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Domain\Strava\Activity\Stream\ReadModel\ActivityStreamDetailsRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Ftp\ReadModel\FtpDetailsRepository;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\ReadModel\KeyValueStore;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Lcobucci\Clock\Clock;
use League\Flysystem\FilesystemOperator;

#[AsCommandHandler]
final readonly class BuildActivityHeatmapChartCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityDetailsRepository $activityDetailsRepository,
        private ActivityStreamDetailsRepository $activityStreamDetailsRepository,
        private FtpDetailsRepository $ftpDetailsRepository,
        private KeyValueStore $keyValueStore,
        private FilesystemOperator $filesystem,
        private Clock $clock
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildActivityHeatmapChart);

        $allActivities = $this->activityDetailsRepository->findAll();
        $athleteBirthday = SerializableDateTime::fromString($this->keyValueStore->find(Key::ATHLETE_BIRTHDAY)->getValue());

        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($allActivities as $activity) {
            try {
                $ftp = $this->ftpDetailsRepository->find($activity->getStartDate());
                $activity->enrichWithFtp($ftp->getFtp());
            } catch (EntityNotFound) {
            }
            $activity->enrichWithAthleteBirthday($athleteBirthday);
            $activity->updateHasDetailedPowerData(
                $this->activityStreamDetailsRepository->hasOneForActivityAndStreamType(
                    activityId: $activity->getId(),
                    streamType: StreamType::WATTS,
                )
            );
        }

        $this->filesystem->write(
            'build/charts/chart-activities-heatmap_1000_180.json',
            Json::encode(
                [
                    'width' => 1000,
                    'height' => 180,
                    'options' => ActivityHeatmapChartBuilder::fromActivities(
                        activities: $this->activityDetailsRepository->findAll(),
                        now: SerializableDateTime::fromDateTimeImmutable($this->clock->now()),
                    )
                        ->withoutTooltip()
                        ->build(),
                ],
                JSON_PRETTY_PRINT),
        );
    }
}
