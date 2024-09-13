<?php

declare(strict_types=1);

namespace App\Domain\Strava\CopyDataToReadDatabase;

use App\Infrastructure\CQRS\DomainCommand;
use Symfony\Component\Console\Output\OutputInterface;

final class CopyDataToReadDatabase extends DomainCommand
{
    public function __construct(
        private readonly OutputInterface $output,
    ) {
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }
}
