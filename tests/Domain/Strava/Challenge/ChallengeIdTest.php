<?php

namespace App\Tests\Domain\Strava\Challenge;

use App\Domain\Strava\Challenge\ChallengeId;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChallengeIdTest extends TestCase
{
    #[DataProvider(methodName: 'provideData')]
    public function testFromDateAndName(SerializableDateTime $date, string $name, ChallengeId $expectedChallengeId): void
    {
        $this->assertEquals(
            $expectedChallengeId,
            ChallengeId::fromDateAndName($date, $name),
        );
    }

    public static function provideData(): array
    {
        return [
            [
                SerializableDateTime::fromString('2022-10-23'),
                'Short name with spaces',
                ChallengeId::fromString('2022-10_short_name_with_spaces'),
            ],
            [
                SerializableDateTime::fromString('2023-01-23'),
                str_repeat('r', 300),
                ChallengeId::fromString('2023-01_'.str_repeat('r', 250)),
            ],
        ];
    }
}
