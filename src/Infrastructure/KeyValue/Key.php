<?php

declare(strict_types=1);

namespace App\Infrastructure\KeyValue;

enum Key: string
{
    case ATHLETE_BIRTHDAY = 'athlete_birthday';
    case IMPORTED_ACTIVITY_STREAMS = 'imported_activity_streams';
}
