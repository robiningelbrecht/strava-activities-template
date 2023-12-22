<?php

declare(strict_types=1);

namespace App\Domain\Strava\CopyDataToReadDatabase;

use App\Infrastructure\CQRS\ConsoleOutputAwareDomainCommand;
use App\Infrastructure\CQRS\DomainCommand;

final class CopyDataToReadDatabase extends DomainCommand
{
    use ConsoleOutputAwareDomainCommand;
}
