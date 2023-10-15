<?php

namespace App\Domain\Strava\Activity\Stream;

enum StreamType: string
{
    case WATTS = 'watts';
    case ALTITUDE = 'altitude';
    case HEART_RATE = 'heartrate';
    case LAT_LNG = 'latlng';
    case CADENCE = 'cadence';
    case VELOCITY = 'velocity_smooth';
}
