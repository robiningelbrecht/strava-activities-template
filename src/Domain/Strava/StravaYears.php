<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use App\Infrastructure\FileSystem\FileRepository;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\YearCollection;

class StravaYears
{
    public function __construct(
        private readonly FileRepository $fileRepository
    ) {
    }

    public function getYears(): YearCollection
    {
        $files = $this->fileRepository->listContents('database');
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
