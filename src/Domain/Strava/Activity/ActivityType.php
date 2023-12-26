<?php

namespace App\Domain\Strava\Activity;

enum ActivityType: string
{
    case RIDE = 'Ride';
    case VIRTUAL_RIDE = 'VirtualRide';

    public function supportsWeather(): bool
    {
        return self::RIDE === $this;
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::RIDE => 'activity-ride',
            self::VIRTUAL_RIDE => 'activity-virtual-ride',
        };
    }

    public function isVirtual(): bool
    {
        return self::RIDE !== $this;
    }
}
