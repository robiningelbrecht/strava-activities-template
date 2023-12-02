<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Calendar\Month;
use App\Infrastructure\ValueObject\Collection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

/**
 * @extends Collection<Activity>
 */
final class ActivityCollection extends Collection
{
    public function getItemClassName(): string
    {
        return Activity::class;
    }

    public function getFirstActivityStartDate(): SerializableDateTime
    {
        $startDate = new SerializableDateTime();
        foreach ($this as $activity) {
            if ($activity->getStartDate()->isAfterOrOn($startDate)) {
                continue;
            }
            $startDate = $activity->getStartDate();
        }

        return $startDate;
    }

    public function filterOnDate(SerializableDateTime $date): ActivityCollection
    {
        return ActivityCollection::fromArray(array_filter(
            $this->toArray(),
            fn (Activity $activity) => $activity->getStartDate()->format('Ymd') === $date->format('Ymd')
        ));
    }

    public function filterOnMonth(Month $month): ActivityCollection
    {
        return ActivityCollection::fromArray(array_filter(
            $this->toArray(),
            fn (Activity $activity) => $activity->getStartDate()->format(Month::MONTH_ID_FORMAT) === $month->getId()
        ));
    }

    public function filterOnDateRange(SerializableDateTime $fromDate, SerializableDateTime $toDate): ActivityCollection
    {
        return ActivityCollection::fromArray(array_filter(
            $this->toArray(),
            fn (Activity $activity) => $activity->getStartDate()->isAfterOrOn($fromDate) && $activity->getStartDate()->isBeforeOrOn($toDate)
        ));
    }

    public function filterOnActivityType(ActivityType $activityType): ActivityCollection
    {
        return ActivityCollection::fromArray(array_filter(
            $this->toArray(),
            fn (Activity $activity) => $activityType === $activity->getType()
        ));
    }
}
