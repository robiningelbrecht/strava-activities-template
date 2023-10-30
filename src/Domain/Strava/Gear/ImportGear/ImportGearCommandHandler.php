<?php

namespace App\Domain\Strava\Gear\ImportGear;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Gear\Gear;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\ReachedStravaApiRateLimits;
use App\Domain\Strava\Strava;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\Time\Sleep;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use GuzzleHttp\Exception\ClientException;
use Lcobucci\Clock\Clock;

#[AsCommandHandler]
final readonly class ImportGearCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private ActivityRepository $activityRepository,
        private GearRepository $gearRepository,
        private ReachedStravaApiRateLimits $reachedStravaApiRateLimits,
        private Clock $clock,
        private Sleep $sleep
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof ImportGear);
        $command->getOutput()->writeln('Importing gear...');

        $gearIds = array_filter($this->activityRepository->findUniqueGearIds());

        foreach ($gearIds as $gearId) {
            try {
                $stravaGear = $this->strava->getGear($gearId);
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

            try {
                $gear = $this->gearRepository->find($gearId);
                $gear->updateDistance($stravaGear['distance'], $stravaGear['converted_distance']);
                $this->gearRepository->update($gear);
            } catch (EntityNotFound) {
                $gear = Gear::create(
                    gearId: $gearId,
                    data: $stravaGear,
                    distanceInMeter: $stravaGear['distance'],
                    createdOn: SerializableDateTime::fromDateTimeImmutable($this->clock->now()),
                );
                $this->gearRepository->add($gear);
            }
            $command->getOutput()->writeln(sprintf('  => Imported/updated gear "%s"', $gear->getName()));
            // Try to avoid Strava rate limits.
            $this->sleep->sweetDreams(10);
        }
    }
}
