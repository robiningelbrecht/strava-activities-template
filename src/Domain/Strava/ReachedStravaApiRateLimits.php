<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use League\Flysystem\FilesystemOperator;

class ReachedStravaApiRateLimits
{
    private const RATE_LIMIT_FILE = 'RATE_LIMITS_REACHED';

    public function __construct(
        private readonly FilesystemOperator $filesystem,
    ) {
    }

    public function clear(): void
    {
        $this->filesystem->delete(self::RATE_LIMIT_FILE);
    }

    public function markAsReached(): void
    {
        $this->filesystem->write(
            self::RATE_LIMIT_FILE,
            '',
        );
    }

    public function hasReached(): bool
    {
        return $this->filesystem->has(self::RATE_LIMIT_FILE);
    }
}
