<?php

declare(strict_types=1);

namespace App\Infrastructure\Time;

final readonly class SystemSleep implements Sleep
{
    public function sweetDreams(int $durationInSeconds): void
    {
        sleep($durationInSeconds);
    }
}
