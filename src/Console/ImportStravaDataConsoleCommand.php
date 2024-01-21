<?php

namespace App\Console;

use App\Domain\Strava\Activity\ImportActivities\ImportActivities;
use App\Domain\Strava\Activity\Stream\CalculateBestStreamAverages\CalculateBestStreamAverages;
use App\Domain\Strava\Activity\Stream\ImportActivityStreams\ImportActivityStreams;
use App\Domain\Strava\Challenge\ImportChallenges\ImportChallenges;
use App\Domain\Strava\CopyDataToReadDatabase\CopyDataToReadDatabase;
use App\Domain\Strava\Gear\ImportGear\ImportGear;
use App\Domain\Strava\MaxResourceUsageHasBeenReached;
use App\Domain\Strava\Segment\ImportSegments\ImportSegments;
use App\Infrastructure\CQRS\CommandBus;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:strava:import-data', description: 'Import Strava data')]
final class ImportStravaDataConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly MaxResourceUsageHasBeenReached $maxResourceUsageHasBeenReached,
        private readonly ResourceUsage $resourceUsage
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->resourceUsage->startTimer();
        $this->maxResourceUsageHasBeenReached->clear();
        // Copy data to read db to determine if we need to add/update data.
        $this->commandBus->dispatch(new CopyDataToReadDatabase($output));
        $this->commandBus->dispatch(new ImportActivities($output, $this->resourceUsage));
        if ($this->resourceUsage->maxExecutionTimeReached()) {
            $this->maxResourceUsageHasBeenReached->markAsReached();

            return Command::SUCCESS;
        }

        // Might have imported new activities, copy them to read db so other import processes are aware of them.
        $this->commandBus->dispatch(new CopyDataToReadDatabase($output));
        $this->commandBus->dispatch(new ImportActivityStreams($output, $this->resourceUsage));
        // @phpstan-ignore-next-line
        if ($this->resourceUsage->maxExecutionTimeReached()) {
            $this->maxResourceUsageHasBeenReached->markAsReached();

            return Command::SUCCESS;
        }

        $this->commandBus->dispatch(new ImportSegments($output));
        $this->commandBus->dispatch(new ImportGear($output));
        $this->commandBus->dispatch(new ImportChallenges($output));
        // Copy data to read db to be able to calculate stream averages.
        $this->commandBus->dispatch(new CopyDataToReadDatabase($output));
        $this->commandBus->dispatch(new CalculateBestStreamAverages($output));

        return Command::SUCCESS;
    }
}
