<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityWasDeleted;
use App\Domain\Strava\Activity\Stream\ReadModel\ActivityStreamDetailsRepository;
use App\Domain\Strava\Activity\Stream\WriteModel\ActivityStreamRepository;
use App\Infrastructure\Attribute\AsEventListener;
use App\Infrastructure\Eventing\EventListener\ConventionBasedEventListener;
use App\Infrastructure\Eventing\EventListener\EventListenerType;

#[AsEventListener(type: EventListenerType::PROCESS_MANAGER)]
final class StreamActivityManager extends ConventionBasedEventListener
{
    public function __construct(
        private readonly ActivityStreamRepository $activityStreamRepository,
        private readonly ActivityStreamDetailsRepository $activityStreamDetailsRepository,
    ) {
        parent::__construct();
    }

    public function reactToActivityWasDeleted(ActivityWasDeleted $event): void
    {
        $segmentEfforts = $this->activityStreamDetailsRepository->findByActivityId($event->getActivityId());
        if ($segmentEfforts->isEmpty()) {
            return;
        }

        foreach ($segmentEfforts as $segmentEffort) {
            $this->activityStreamRepository->delete($segmentEffort);
        }
    }
}
