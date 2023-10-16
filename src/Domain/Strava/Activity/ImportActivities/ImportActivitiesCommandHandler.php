<?php

namespace App\Domain\Strava\Activity\ImportActivities;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\StravaActivityRepository;
use App\Domain\Strava\ReachedStravaApiRateLimits;
use App\Domain\Strava\Strava;
use App\Domain\Weather\OpenMeteo\OpenMeteo;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use GuzzleHttp\Exception\ClientException;
use League\Flysystem\Filesystem;
use Ramsey\Uuid\Rfc4122\UuidV5;

#[AsCommandHandler]
final readonly class ImportActivitiesCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private OpenMeteo $openMeteo,
        private StravaActivityRepository $stravaActivityRepository,
        private Filesystem $filesystem,
        private ReachedStravaApiRateLimits $reachedStravaApiRateLimits
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof ImportActivities);
        $command->getOutput()->writeln('Importing activities...');

        $athlete = $this->strava->getAthlete();

        foreach ($this->strava->getActivities() ?? [] as $stravaActivity) {
            if (!$activityType = ActivityType::tryFrom($stravaActivity['type'])) {
                continue;
            }

            try {
                $activity = $this->stravaActivityRepository->find($stravaActivity['id']);
                $activity->updateKudoCount($stravaActivity['kudos_count'] ?? 0);
                $this->stravaActivityRepository->update($activity);
                $command->getOutput()->writeln(sprintf('  => Updated activity "%s"', $activity->getName()));
            } catch (EntityNotFound) {
                try {
                    $activity = Activity::create(
                        activityId: $stravaActivity['id'],
                        startDateTime: SerializableDateTime::createFromFormat(
                            Activity::DATE_TIME_FORMAT,
                            $stravaActivity['start_date_local']
                        ),
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

                            $extension = pathinfo(parse_url($photo['urls'][5000], PHP_URL_PATH), PATHINFO_EXTENSION);
                            $imagePath = sprintf('files/activities/%s.%s', UuidV5::uuid1(), $extension);
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

                    $this->stravaActivityRepository->add($activity);
                    $command->getOutput()->writeln(sprintf('  => Imported activity "%s"', $activity->getName()));
                    // Try to avoid Strava rate limits.
                    sleep(10);
                } catch (ClientException $exception) {
                    if (429 !== $exception->getResponse()?->getStatusCode()) {
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
