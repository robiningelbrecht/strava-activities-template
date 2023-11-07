<?php

declare(strict_types=1);

namespace App\Domain\Strava\Challenge;

use App\Infrastructure\ValueObject\String\NonEmptyStringLiteral;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

class ChallengeId extends NonEmptyStringLiteral
{
    public static function fromDateAndName(
        SerializableDateTime $createdOn,
        string $name,
    ): self {
        /** @var string $sanitizedString */
        $sanitizedString = preg_replace("/\s+/", '_', substr($name, 0, 250));

        return self::fromString(
            $createdOn->format('Y-m_').strtolower($sanitizedString)
        );
    }
}
