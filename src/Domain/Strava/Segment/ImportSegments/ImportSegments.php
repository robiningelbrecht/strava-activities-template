<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\ImportSegments;

use App\Infrastructure\CQRS\ConsoleOutputAwareDomainCommand;
use App\Infrastructure\CQRS\DomainCommand;

final class ImportSegments extends DomainCommand
{
    use ConsoleOutputAwareDomainCommand;
}
