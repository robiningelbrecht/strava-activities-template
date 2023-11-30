<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

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
}
