<?php

namespace App\Domain\Strava\Activity\ImportActivities;

use App\Domain\Nominatim\Nominatim;
use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Domain\Strava\Activity\WriteModel\ActivityRepository;
use App\Domain\Strava\Gear\GearId;
use App\Domain\Strava\MaxResourceUsageHasBeenReached;
use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaErrorStatusCode;
use App\Domain\Weather\OpenMeteo\OpenMeteo;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\KeyValue\WriteModel\KeyValueStore;
use App\Infrastructure\Time\Sleep;
use App\Infrastructure\ValueObject\Geography\Coordinate;
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
        private Nominatim $nominatim,
        private ActivityRepository $activityRepository,
        private ActivityDetailsRepository $activityDetailsRepository,
        private KeyValueStore $keyValueStore,
        private FilesystemOperator $filesystem,
        private MaxResourceUsageHasBeenReached $maxResourceUsageHasBeenReached,
        private UuidFactory $uuidFactory,
        private Sleep $sleep,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof ImportActivities);
        $command->getOutput()->writeln('Importing activities...');

        $athlete = $this->strava->getAthlete();
        // Store in KeyValue store, so we don't need to query Strava again.
        $this->keyValueStore->save(KeyValue::fromState(
            key: Key::ATHLETE_ID,
            value: Value::fromString($athlete['id'])
        ));

        $allActivityIds = $this->activityDetailsRepository->findActivityIds();
        $activityIdsDelete = array_combine(
            $allActivityIds->map(fn (ActivityId $activityId) => (string) $activityId),
            $allActivityIds->toArray(),
        );

        foreach ($this->strava->getActivities() as $stravaActivity) {
            if ($command->getResourceUsage()->maxExecutionTimeReached()) {
                return;
            }
            if (!$activityType = ActivityType::tryFrom($stravaActivity['type'])) {
                continue;
            }
            $activityId = ActivityId::fromUnprefixed((string) $stravaActivity['id']);
            try {
                $activity = $this->activityDetailsRepository->find($activityId);
                $activity
                    ->updateName($stravaActivity['name'])
                    ->updateDescription($stravaActivity['description'] ?? '')
                    ->updateKudoCount($stravaActivity['kudos_count'] ?? 0)
                    ->updateGearId(GearId::fromOptionalUnprefixed($stravaActivity['gear_id'] ?? null));

                if (!$activity->getLocation() && $activityType->supportsReverseGeocoding()
                    && $activity->getLatitude() && $activity->getLongitude()) {
                    $reverseGeocodedAddress = $this->nominatim->reverseGeocode(Coordinate::createFromLatAndLng(
                        latitude: $activity->getLatitude(),
                        longitude: $activity->getLongitude(),
                    ));

                    $activity->updateLocation($reverseGeocodedAddress);
                    $this->sleep->sweetDreams(1);
                }

                $this->activityRepository->update($activity);
                unset($activityIdsDelete[(string) $activity->getId()]);
                $command->getOutput()->writeln(sprintf('  => Updated activity "%s"', $activity->getName()));
            } catch (EntityNotFound) {
                try {
                    $startDate = SerializableDateTime::createFromFormat(
                        Activity::DATE_TIME_FORMAT,
                        $stravaActivity['start_date_local']
                    );
                    $activity = Activity::create(
                        activityId: $activityId,
                        startDateTime: $startDate,
                        data: [
                            ...$this->strava->getActivity($activityId),
                            'athlete_weight' => $athlete['weight'],
                        ],
                        gearId: GearId::fromOptionalUnprefixed($stravaActivity['gear_id'] ?? null)
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

                    if ($activityType->supportsReverseGeocoding() && $activity->getLatitude() && $activity->getLongitude()) {
                        $reverseGeocodedAddress = $this->nominatim->reverseGeocode(Coordinate::createFromLatAndLng(
                            latitude: $activity->getLatitude(),
                            longitude: $activity->getLongitude(),
                        ));

                        $activity->updateLocation($reverseGeocodedAddress);
                    }

                    $this->activityRepository->add($activity);
                    unset($activityIdsDelete[(string) $activity->getId()]);
                    $command->getOutput()->writeln(sprintf('  => Imported activity "%s"', $activity->getName()));
                    // Try to avoid Strava rate limits.
                    $this->sleep->sweetDreams(10);
                } catch (ClientException $exception) {
                    if (!StravaErrorStatusCode::tryFrom(
                        $exception->getResponse()->getStatusCode()
                    )) {
                        // Re-throw, we only want to catch supported error codes.
                        throw $exception;
                    }
                    // This will allow initial imports with a lot of activities to proceed the next day.
                    // This occurs when we exceed Strava API rate limits or throws an unexpected error.
                    $this->maxResourceUsageHasBeenReached->markAsReached();
                    $command->getOutput()->writeln('<error>You probably reached Strava API rate limits. You will need to import the rest of your activities tomorrow</error>');

                    return;
                }
            }
        }

        if (empty($activityIdsDelete)) {
            return;
        }

        foreach ($activityIdsDelete as $activityId) {
            $activity = $this->activityDetailsRepository->find($activityId);
            $activity->delete();
            $this->activityRepository->delete($activity);

            $command->getOutput()->writeln(sprintf('  => Deleted activity "%s"', $activity->getName()));
        }
    }
}
