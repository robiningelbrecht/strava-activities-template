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

    public function supportsReverseGeocoding(): bool
    {
        // return self::RIDE === $this;
        return false;
    }

    public function isVirtual(): bool
    {
        return self::RIDE !== $this;
    }
}
