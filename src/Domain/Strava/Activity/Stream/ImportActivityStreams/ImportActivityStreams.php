<?php

namespace App\Domain\Strava\Activity\Stream\ImportActivityStreams;

use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportActivityStreams extends DomainCommand
{
    public function __construct(
        private readonly OutputInterface $output,
        private readonly ResourceUsage $resourceUsage,
    ) {
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function getResourceUsage(): ResourceUsage
    {
        return $this->resourceUsage;
    }
}
