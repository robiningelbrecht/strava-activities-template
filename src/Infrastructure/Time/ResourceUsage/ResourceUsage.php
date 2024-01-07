<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\ResourceUsage;

interface ResourceUsage
{
    public function format(float $durationInMicroSeconds): string;
}
