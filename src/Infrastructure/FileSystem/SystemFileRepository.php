<?php

declare(strict_types=1);

namespace App\Infrastructure\FileSystem;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;

final readonly class SystemFileRepository implements FileRepository
{
    public function __construct(
        private FilesystemOperator $filesystemOperator,
    ) {
    }

    /**
     * @return string[]
     */
    public function listContents(string $path): array
    {
        return $this->filesystemOperator->listContents($path)
            ->filter(fn (StorageAttributes $attributes) => $attributes->isFile())
            ->map(fn (StorageAttributes $attributes) => $attributes->path())
            ->toArray();
    }
}
