<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:strava:vacuum', description: 'Vacuum database')]
final class VacuumDatabaseConsoleCommand extends Command
{
    public function __construct(
        private readonly ConnectionFactory $connectionFactory,
        private readonly ActivityRepository $activityRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->connectionFactory->getDefault()->executeStatement('VACUUM');
        foreach ($this->activityRepository->findUniqueYears() as $year) {
            $this->connectionFactory->getForYear($year)->executeStatement('VACUUM');
        }

        $output->writeln('Databases got vacuumed 🧹');

        return Command::SUCCESS;
    }
}
