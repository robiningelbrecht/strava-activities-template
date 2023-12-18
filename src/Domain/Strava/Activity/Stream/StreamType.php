<?php

namespace App\Domain\Strava\Activity\Stream;

enum StreamType: string
{
    case WATTS = 'watts';
    case HEART_RATE = 'heartrate';
    case CADENCE = 'cadence';
    case HACK = 'hack';
}
