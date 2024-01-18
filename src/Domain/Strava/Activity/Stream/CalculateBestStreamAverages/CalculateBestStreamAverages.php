<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CalculateBestStreamAverages;

use App\Infrastructure\CQRS\ConsoleOutputAwareDomainCommand;
use App\Infrastructure\CQRS\DomainCommand;

final class CalculateBestStreamAverages extends DomainCommand
{
    use ConsoleOutputAwareDomainCommand;
}
