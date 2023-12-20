<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Time;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Year>
 */
class YearCollection extends Collection
{
    public function getItemClassName(): string
    {
        return Year::class;
    }
}
