<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Infrastructure\Eventing\DomainEvent;

final readonly class ActivityWasDeleted extends DomainEvent
{
    public function __construct(
        private ActivityId $activityId
    ) {
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }
}
