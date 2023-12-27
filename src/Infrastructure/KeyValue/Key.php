<?php

declare(strict_types=1);

namespace App\Infrastructure\KeyValue;

enum Key: string
{
    case ATHLETE_BIRTHDAY = 'athlete_birthday';
    case ATHLETE_ID = 'athlete_id';
}
