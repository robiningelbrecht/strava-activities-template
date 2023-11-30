<?php

namespace App\Domain\Strava\Activity\ImportActivities;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\ReachedStravaApiRateLimits;
use App\Domain\Strava\Strava;
use App\Domain\Weather\OpenMeteo\OpenMeteo;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Time\Sleep;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\UuidFactory;
use GuzzleHttp\Exception\ClientException;
use League\Flysystem\FilesystemOperator;

#[AsCommandHandler]
final readonly class ImportActivitiesCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private OpenMeteo $openMeteo,
        private ActivityRepository $activityRepository,
        private FilesystemOperator $filesystem,
        private ReachedStravaApiRateLimits $reachedStravaApiRateLimits,
        private UuidFactory $uuidFactory,
        private Sleep $sleep,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof ImportActivities);
        $command->getOutput()->writeln('Importing activities...');

        $athlete = $this->strava->getAthlete();

        foreach ($this->strava->getActivities() as $stravaActivity) {
            if (!$activityType = ActivityType::tryFrom($stravaActivity['type'])) {
                continue;
            }

            try {
                $activity = $this->activityRepository->find($stravaActivity['id']);
                $activity
                    ->updateName($stravaActivity['name'])
                    ->updateKudoCount($stravaActivity['kudos_count'] ?? 0)
                    ->updateGearId($stravaActivity['gear_id'] ?? null);
                $this->activityRepository->update($activity);
                $command->getOutput()->writeln(sprintf('  => Updated activity "%s"', $activity->getName()));
            } catch (EntityNotFound) {
                try {
                    /** @var SerializableDateTime $startDate */
                    $startDate = SerializableDateTime::createFromFormat(
                        Activity::DATE_TIME_FORMAT,
                        $stravaActivity['start_date_local']
                    );
                    $activity = Activity::create(
                        activityId: $stravaActivity['id'],
                        startDateTime: $startDate,
                        data: [
                            ...$this->strava->getActivity($stravaActivity['id']),
                            'athlete_weight' => $athlete['weight'],
                        ],
                        gearId: $stravaActivity['gear_id'] ?? null
                    );

                    $localImagePaths = [];

                    if ($activity->getTotalImageCount() > 0) {
                        $photos = $this->strava->getActivityPhotos($activity->getId());
                        foreach ($photos as $photo) {
                            if (empty($photo['urls'][5000])) {
                                continue;
                            }

                            /** @var string $urlPath */
                            $urlPath = parse_url($photo['urls'][5000], PHP_URL_PATH);
                            $extension = pathinfo($urlPath, PATHINFO_EXTENSION);
                            $imagePath = sprintf('files/activities/%s.%s', $this->uuidFactory->random(), $extension);
                            $this->filesystem->write(
                                $imagePath,
                                $this->strava->downloadImage($photo['urls'][5000])
                            );
                            $localImagePaths[] = $imagePath;
                        }
                        $activity->updateLocalImagePaths($localImagePaths);
                    }

                    if ($activityType->supportsWeather() && $activity->getLatitude() && $activity->getLongitude()) {
                        $weather = $this->openMeteo->getWeatherStats(
                            $activity->getLatitude(),
                            $activity->getLongitude(),
                            $activity->getStartDate()
                        );
                        $activity->updateWeather($weather);
                    }

                    $this->activityRepository->add($activity);
                    $command->getOutput()->writeln(sprintf('  => Imported activity "%s"', $activity->getName()));
                    // Try to avoid Strava rate limits.
                    $this->sleep->sweetDreams(10);
                } catch (ClientException $exception) {
                    if (429 !== $exception->getResponse()->getStatusCode()) {
                        // Re-throw, we only want to catch "429 Too Many Requests".
                        throw $exception;
                    }
                    // This will allow initial imports with a lot of activities to proceed the next day.
                    // This occurs when we exceed Strava API rate limits.
                    $this->reachedStravaApiRateLimits->markAsReached();
                    $command->getOutput()->writeln('<error>You reached Strava API rate limits. You will need to import the rest of your activities tomorrow</error>');
                    break;
                }
            }
        }
    }
}
