<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Command;

use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\ValueObject\Time\Year;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationLoader;
use Doctrine\Migrations\DependencyFactory;
use Lcobucci\Clock\Clock;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'migrations:migrate', description: 'Execute a migration to a specified version or the latest available version.')]
final class MigrateCommand extends Command
{
    private readonly ConnectionFactory $connectionFactory;
    private readonly Clock $clock;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigurationLoader $migrationConfig,
    ) {
        parent::__construct();
        $this->connectionFactory = $this->container->get(ConnectionFactory::class);
        $this->clock = $this->container->get(Clock::class);
    }

    protected function configure(): void
    {
        $this
            ->setAliases(['migrate'])
            ->setDescription(
                'Execute a migration to a specified version or the latest available version.',
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $question = 'WARNING! You are about to execute a migration that could result in schema changes and data loss. Are you sure you wish to continue?';
        if (!$io->confirm($question)) {
            $io->error('Migration cancelled!');

            return 3;
        }

        $connections = [
            $this->connectionFactory->getDefault(),
            $this->connectionFactory->getReadOnly(),
            ...array_map(
                fn (int $year) => $this->connectionFactory->getForYear(Year::fromInt($year)),
                range(2000, (int) $this->clock->now()->format('Y')),
            ),
        ];

        foreach ($connections as $connection) {
            $dependencyFactory = DependencyFactory::fromConnection(
                configurationLoader: $this->migrationConfig,
                connectionLoader: new ExistingConnection($connection)
            );
            $dependencyFactory->getMetadataStorage()->ensureInitialized();

            $version = $dependencyFactory->getVersionAliasResolver()->resolveVersionAlias('latest');
            $plan = $dependencyFactory->getMigrationPlanCalculator()->getPlanUntilVersion($version);
            /* @phpstan-ignore-next-line */
            $databasePathParts = explode('/', $dependencyFactory->getConnection()->getParams()['path']);
            $databaseName = end($databasePathParts);
            if (0 === count($plan)) {
                $io->writeln(sprintf(
                    '<fg=black;bg=green>Database "%s" already at the latest version ("%s")</>',
                    $databaseName,
                    $version,
                ));

                continue;
            }

            $migratorConfigurationFactory = $dependencyFactory->getConsoleInputMigratorConfigurationFactory();
            $migratorConfiguration = $migratorConfigurationFactory->getMigratorConfiguration($input);

            $dependencyFactory->getMigrator()->migrate(
                $plan,
                $migratorConfiguration
            );

            $io->writeln(sprintf(
                '<fg=black;bg=green>Successfully migrated database "%s" to version: %s</>',
                $databaseName,
                $version,
            ));
        }

        return Command::SUCCESS;
    }
}
