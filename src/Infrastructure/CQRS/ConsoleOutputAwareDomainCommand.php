<?php

namespace App\Infrastructure\CQRS;

use Symfony\Component\Console\Output\OutputInterface;

trait ConsoleOutputAwareDomainCommand
{
    public function __construct(
        private readonly OutputInterface $output
    ) {
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }
}
