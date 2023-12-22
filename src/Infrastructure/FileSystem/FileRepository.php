<?php

declare(strict_types=1);

namespace App\Infrastructure\FileSystem;

interface FileRepository
{
    /**
     * @return string[]
     */
    public function listContents(string $path): array;
}
