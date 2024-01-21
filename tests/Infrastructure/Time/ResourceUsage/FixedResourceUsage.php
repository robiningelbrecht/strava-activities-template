<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Time\ResourceUsage;

use App\Infrastructure\Time\ResourceUsage\ResourceUsage;

final readonly class FixedResourceUsage implements ResourceUsage
{
    public function __construct(
        private bool $maxExecutionTimeReached = false,
    ) {
    }

    public function startTimer(): void
    {
        // TODO: Implement startTimer() method.
    }

    public function stopTimer(): void
    {
        // TODO: Implement stopTimer() method.
    }

    public function maxExecutionTimeReached(): bool
    {
        return $this->maxExecutionTimeReached;
    }

    public function format(): string
    {
        return 'Time: 10s, Memory: 45MB';
    }
}
