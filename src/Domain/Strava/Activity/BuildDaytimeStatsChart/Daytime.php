<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BuildDaytimeStatsChart;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

enum Daytime: string
{
    case MORNING = 'Morning';
    case AFTERNOON = 'Afternoon';
    case EVENING = 'Evening';
    case NIGHT = 'Night';

    public static function fromSerializableDateTime(SerializableDateTime $dateTime): self
    {
        $hour = $dateTime->getHourWithoutLeadingZero();

        return match (true) {
            $hour >= 6 && $hour < 12 => self::MORNING, //  6 - 12
            $hour >= 12 && $hour < 17 => self::AFTERNOON, // 12 - 17
            $hour >= 17 && $hour < 23 => self::EVENING, //  17 -23
            $hour >= 23,
            $hour >= 0 => self::NIGHT, // 0 - 6,
            default => throw new \RuntimeException('Could not determine daytime'),
        };
    }

    public function getEmoji(): string
    {
        return match ($this) {
            self::MORNING => '🌞',
            self::AFTERNOON => '🌆',
            self::EVENING => '🌃',
            self::NIGHT => '🌙',
        };
    }

    /**
     * @return array<int, int>
     */
    public function getHours(): array
    {
        return match ($this) {
            self::MORNING => [6, 12],
            self::AFTERNOON => [12, 17],
            self::EVENING => [17, 23],
            self::NIGHT => [23, 6],
        };
    }
}
