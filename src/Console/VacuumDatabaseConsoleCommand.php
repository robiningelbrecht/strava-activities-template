<?php

declare(strict_types=1);

namespace App\Console;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:strava:vacuum', description: 'Vacuum database')]
final class VacuumDatabaseConsoleCommand extends Command
{
    public function __construct(
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->connection->executeStatement('VACUUM');

        $output->writeln('Database got vacuumed 🧹');

        return Command::SUCCESS;
    }
}
