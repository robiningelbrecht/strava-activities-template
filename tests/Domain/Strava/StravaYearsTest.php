<?php

namespace App\Tests\Domain\Strava;

use App\Domain\Strava\StravaYears;
use App\Infrastructure\FileSystem\FileRepository;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\YearCollection;
use PHPUnit\Framework\TestCase;

class StravaYearsTest extends TestCase
{
    public function testGetYears(): void
    {
        $fileRepository = $this->createMock(FileRepository::class);

        $fileRepository
            ->expects($this->once())
            ->method('listContents')
            ->with('database')
            ->willReturn(['database/db.strava', 'database/db.strava-2023', 'database/db.strava-2024', 'database/db.strava-test']);

        $this->assertEquals(
            YearCollection::fromArray([Year::fromInt(2023), Year::fromInt(2024)]),
            (new StravaYears($fileRepository))->getYears(),
        );
    }
}
