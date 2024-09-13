<?php

namespace App\Domain\Strava\Activity\Stream\ImportActivityStreams;

use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Domain\Strava\Activity\Stream\ReadModel\ActivityStreamDetailsRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\WriteModel\ActivityStreamRepository;
use App\Domain\Strava\MaxResourceUsageHasBeenReached;
use App\Domain\Strava\Strava;
use App\Domain\Strava\StravaErrorStatusCode;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Time\Sleep;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

#[AsCommandHandler]
final readonly class ImportActivityStreamsCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private ActivityDetailsRepository $activityDetailsRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private ActivityStreamDetailsRepository $activityStreamDetailsRepository,
        private MaxResourceUsageHasBeenReached $maxResourceUsageHasBeenReached,
        private Sleep $sleep,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof ImportActivityStreams);
        $command->getOutput()->writeln('Importing activity streams...');

        foreach ($this->activityDetailsRepository->findActivityIds() as $activityId) {
            if ($command->getResourceUsage()->maxExecutionTimeReached()) {
                return;
            }
            if ($this->activityStreamDetailsRepository->isImportedForActivity($activityId)) {
                // Streams for this activity have been imported already, skip.
                continue;
            }
            $stravaStreams = [];
            try {
                $stravaStreams = $this->strava->getAllActivityStreams($activityId);
            } catch (ClientException|RequestException $exception) {
                if (!$exception->getResponse() || !in_array($exception->getResponse()->getStatusCode(), [404, ...array_map(
                    fn (StravaErrorStatusCode $errorStatusCode) => $errorStatusCode->value,
                    StravaErrorStatusCode::cases(),
                )])) {
                    // Re-throw, we only want to catch supported error codes.
                    throw $exception;
                }

                if (StravaErrorStatusCode::tryFrom(
                    $exception->getResponse()->getStatusCode()
                )) {
                    // This will allow initial imports with a lot of activities to proceed the next day.
                    // This occurs when we exceed Strava API rate limits or throws an unexpected error.
                    $this->maxResourceUsageHasBeenReached->markAsReached();
                    $command->getOutput()->writeln('<error>You probably reached Strava API rate limits. You will need to import the rest of your activities tomorrow</error>');
                    break;
                }

                if (404 === $exception->getResponse()->getStatusCode()) {
                    continue;
                }
            }

            $stravaStreams = array_filter(
                $stravaStreams,
                fn (array $stravaStream): bool => !is_null(StreamType::tryFrom($stravaStream['type']))
            );
            if (empty($stravaStreams)) {
                // We need this hack for activities that do not have streams.
                // This way we can "tag" them as imported.
                $stravaStreams[] = [
                    'type' => StreamType::HACK->value,
                    'data' => [],
                ];
            }

            $activity = $this->activityDetailsRepository->find($activityId);
            foreach ($stravaStreams as $stravaStream) {
                if (!$streamType = StreamType::tryFrom($stravaStream['type'])) {
                    continue;
                }

                $stream = ActivityStream::create(
                    activityId: $activityId,
                    streamType: $streamType,
                    streamData: $stravaStream['data'],
                    createdOn: $activity->getStartDate(),
                );
                $this->activityStreamRepository->add($stream);
                $command->getOutput()->writeln(sprintf('  => Imported activity stream "%s"', $stream->getName()));
            }

            // Try to avoid Strava rate limits.
            $this->sleep->sweetDreams(10);
        }
    }
}
