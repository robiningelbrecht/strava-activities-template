<?php

declare(strict_types=1);

namespace App\Domain\Strava;

enum StravaErrorStatusCode: int
{
    case TOO_MANY_REQUESTS = 429;
    case BAD_GATEWAY = 502;
    case SERVER_ERROR = 597;
}
