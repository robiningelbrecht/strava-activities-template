<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Time\ResourceUsage;

use App\Infrastructure\Time\ResourceUsage\ResourceUsage;

final readonly class FixedResourceUsage implements ResourceUsage
{
    public function format(float $durationInMicroSeconds): string
    {
        return 'Time: 10s, Memory: 45MB';
    }
}
