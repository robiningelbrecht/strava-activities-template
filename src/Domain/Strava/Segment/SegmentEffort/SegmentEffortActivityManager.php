<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort;

use App\Domain\Strava\Activity\ActivityWasDeleted;
use App\Domain\Strava\Segment\SegmentEffort\ReadModel\SegmentEffortDetailsRepository;
use App\Domain\Strava\Segment\SegmentEffort\WriteModel\SegmentEffortRepository;
use App\Infrastructure\Attribute\AsEventListener;
use App\Infrastructure\Eventing\EventListener\ConventionBasedEventListener;
use App\Infrastructure\Eventing\EventListener\EventListenerType;

#[AsEventListener(type: EventListenerType::PROCESS_MANAGER)]
final class SegmentEffortActivityManager extends ConventionBasedEventListener
{
    public function __construct(
        private readonly SegmentEffortRepository $segmentEffortRepository,
        private readonly SegmentEffortDetailsRepository $segmentEffortDetailsRepository,
    ) {
        parent::__construct();
    }

    public function reactToActivityWasDeleted(ActivityWasDeleted $event): void
    {
        $segmentEfforts = $this->segmentEffortDetailsRepository->findByActivityId($event->getActivityId());
        if ($segmentEfforts->isEmpty()) {
            return;
        }

        foreach ($segmentEfforts as $segmentEffort) {
            $this->segmentEffortRepository->delete($segmentEffort);
        }
    }
}
