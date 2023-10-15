<?php

namespace App\Domain\Strava\Activity\Stream\ImportActivityStreams;

use App\Infrastructure\CQRS\ConsoleOutputAwareDomainCommand;
use App\Infrastructure\CQRS\DomainCommand;

final class ImportActivityStreams extends DomainCommand
{
    use ConsoleOutputAwareDomainCommand;
}
