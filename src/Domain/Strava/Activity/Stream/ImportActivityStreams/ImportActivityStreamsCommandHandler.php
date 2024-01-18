<?php

namespace App\Domain\Strava\Activity\Stream\ImportActivityStreams;

use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Domain\Strava\Activity\Stream\ReadModel\ActivityStreamDetailsRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\WriteModel\ActivityStreamRepository;
use App\Domain\Strava\ReachedStravaApiRateLimits;
use App\Domain\Strava\Strava;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Time\Sleep;
use GuzzleHttp\Exception\ClientException;

#[AsCommandHandler]
final readonly class ImportActivityStreamsCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private ActivityDetailsRepository $activityDetailsRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private ActivityStreamDetailsRepository $activityStreamDetailsRepository,
        private ReachedStravaApiRateLimits $reachedStravaApiRateLimits,
        private Sleep $sleep,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof ImportActivityStreams);
        $command->getOutput()->writeln('Importing activity streams...');

        foreach ($this->activityDetailsRepository->findActivityIds() as $activityId) {
            if ($this->activityStreamDetailsRepository->isImportedForActivity($activityId)) {
                // Streams for this activity have been imported already, skip.
                continue;
            }
            $stravaStreams = [];
            try {
                $stravaStreams = $this->strava->getAllActivityStreams($activityId);
            } catch (ClientException $exception) {
                if (!in_array($exception->getResponse()->getStatusCode(), [404, 429])) {
                    // Re-throw, we only want to catch "429 Too Many Requests".
                    throw $exception;
                }
                if (429 === $exception->getResponse()->getStatusCode()) {
                    // This will allow initial imports with a lot of activities to proceed the next day.
                    // This occurs when we exceed Strava API rate limits.
                    $this->reachedStravaApiRateLimits->markAsReached();
                    $command->getOutput()->writeln('<error>You reached Strava API rate limits. You will need to import the rest of your activities tomorrow</error>');

                    break;
                }
                if (404 === $exception->getResponse()->getStatusCode()) {
                    continue;
                }
            }

            $stravaStreams = array_filter(
                $stravaStreams,
                fn (array $stravaStream) => StreamType::tryFrom($stravaStream['type'])
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
