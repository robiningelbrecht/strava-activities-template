<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\ImportSegments;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Domain\Strava\Activity\WriteModel\ActivityRepository;
use App\Domain\Strava\Segment\ReadModel\SegmentDetailsRepository;
use App\Domain\Strava\Segment\Segment;
use App\Domain\Strava\Segment\SegmentEffort\ReadModel\SegmentEffortDetailsRepository;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Strava\Segment\SegmentEffort\WriteModel\SegmentEffortRepository;
use App\Domain\Strava\Segment\WriteModel\SegmentRepository;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

#[AsCommandHandler]
final readonly class ImportSegmentsCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityDetailsRepository $activityDetailsRepository,
        private ActivityRepository $activityRepository,
        private SegmentRepository $segmentRepository,
        private SegmentDetailsRepository $segmentDetailsRepository,
        private SegmentEffortRepository $segmentEffortRepository,
        private SegmentEffortDetailsRepository $segmentEffortDetailsRepository
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof ImportSegments);
        $command->getOutput()->writeln('Importing segments and efforts...');

        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($this->activityDetailsRepository->findAll() as $activity) {
            if (!$segmentEfforts = $activity->getSegmentEfforts()) {
                // No segments or we already imported and delete them from the activity.
                continue;
            }

            foreach ($segmentEfforts as $activitySegmentEffort) {
                $activitySegment = $activitySegmentEffort['segment'];
                $segment = Segment::create(
                    segmentId: $activitySegment['id'],
                    name: Name::fromString($activitySegment['name']),
                    data: $activitySegment,
                );

                try {
                    $this->segmentDetailsRepository->find($segment->getId());
                } catch (EntityNotFound) {
                    $this->segmentRepository->add($segment);
                    $command->getOutput()->writeln(sprintf('  => Added segment "%s"', $segment->getName()));
                }

                try {
                    $segmentEffort = $this->segmentEffortDetailsRepository->find($activitySegmentEffort['id']);
                    $this->segmentEffortRepository->update($segmentEffort);
                } catch (EntityNotFound) {
                    $this->segmentEffortRepository->add(SegmentEffort::create(
                        segmentEffortId: $activitySegmentEffort['id'],
                        segmentId: $segment->getId(),
                        activityId: $activity->getId(),
                        startDateTime: SerializableDateTime::createFromFormat(
                            Activity::DATE_TIME_FORMAT,
                            $activitySegmentEffort['start_date_local']
                        ),
                        data: $activitySegmentEffort
                    ));
                    $command->getOutput()->writeln(sprintf('  => Added segment effort for "%s"', $segment->getName()));
                }
            }

            // Delete segments from data on activity to reduce DB size?.
            $activity->removeSegments();
            $this->activityRepository->update($activity);
        }
    }
}
