<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\ImportSegments;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Segment\Segment;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentRepository;
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
        private ActivityRepository $activityRepository,
        private SegmentRepository $segmentRepository,
        private SegmentEffortRepository $segmentEffortRepository
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof ImportSegments);
        $command->getOutput()->writeln('Importing segments and efforts...');

        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($this->activityRepository->findAll() as $activity) {
            if (!$segmentEfforts = $activity->getSegmentEfforts()) {
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
                    $this->segmentRepository->find($segment->getId());
                } catch (EntityNotFound) {
                    $this->segmentRepository->add($segment);
                    $command->getOutput()->writeln(sprintf('  => Added segment "%s"', $segment->getName()));
                }

                try {
                    $segmentEffort = $this->segmentEffortRepository->find($activitySegmentEffort['id']);
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
        }
    }
}
