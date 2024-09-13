<?php

declare(strict_types=1);

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Ftp>
 */
class Ftps extends Collection
{
    public function getItemClassName(): string
    {
        return Ftp::class;
    }
}
