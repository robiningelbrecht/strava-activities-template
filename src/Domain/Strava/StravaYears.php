<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\YearCollection;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;

/**
 * @codeCoverageIgnore
 */
class StravaYears
{
    public function __construct(
        private readonly FilesystemOperator $filesystemOperator
    ) {
    }

    public function getYears(): YearCollection
    {
        $files = $this->filesystemOperator->listContents('database')
            ->filter(fn (StorageAttributes $attributes) => $attributes->isFile())
            ->map(fn (StorageAttributes $attributes) => $attributes->path())
            ->toArray();

        $years = YearCollection::empty();
        foreach ($files as $file) {
            if (!preg_match('/database\/db.strava-(?<match>[\d]{4})/', $file, $match)) {
                continue;
            }
            $years->add(Year::fromInt((int) $match['match']));
        }

        return $years;
    }
}
