<?php

namespace App\Console;

use App\Domain\Strava\Activity\ImportActivities\ImportActivities;
use App\Domain\Strava\Activity\Stream\ImportActivityStreams\ImportActivityStreams;
use App\Domain\Strava\Challenge\ImportChallenges\ImportChallenges;
use App\Domain\Strava\Gear\ImportGear\ImportGear;
use App\Domain\Strava\ReachedStravaApiRateLimits;
use App\Infrastructure\CQRS\CommandBus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:strava:import-data', description: 'Import Strava data')]
final class ImportStravaDataConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ReachedStravaApiRateLimits $reachedStravaApiRateLimits
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->reachedStravaApiRateLimits->clear();
        $this->commandBus->dispatch(new ImportActivities($output));
        $this->commandBus->dispatch(new ImportActivityStreams($output));
        $this->commandBus->dispatch(new ImportGear($output));
        $this->commandBus->dispatch(new ImportChallenges($output));

        return Command::SUCCESS;
    }
}
