<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use League\Flysystem\FilesystemOperator;

class MaxResourceUsageHasBeenReached
{
    private const FILE_NAME = 'MAX_RESOURCE_USAGE_REACHED';

    public function __construct(
        private readonly FilesystemOperator $filesystem,
    ) {
    }

    public function clear(): void
    {
        $this->filesystem->delete(self::FILE_NAME);
    }

    public function markAsReached(): void
    {
        $this->filesystem->write(
            self::FILE_NAME,
            '',
        );
    }

    public function hasReached(): bool
    {
        return $this->filesystem->has(self::FILE_NAME);
    }
}
