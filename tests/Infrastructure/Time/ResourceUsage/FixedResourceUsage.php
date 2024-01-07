<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Time\ResourceUsage;

use App\Infrastructure\Time\ResourceUsage\ResourceUsage;

final readonly class FixedResourceUsage implements ResourceUsage
{
    public function startTimer(): void
    {
        // TODO: Implement startTimer() method.
    }

    public function stopTimer(): void
    {
        // TODO: Implement stopTimer() method.
    }

    public function format(): string
    {
        return 'Time: 10s, Memory: 45MB';
    }
}
