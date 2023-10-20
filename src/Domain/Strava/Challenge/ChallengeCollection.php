<?php

declare(strict_types=1);

namespace App\Domain\Strava\Challenge;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Challenge>
 */
class ChallengeCollection extends Collection
{
    public function getItemClassName(): string
    {
        return Challenge::class;
    }
}
