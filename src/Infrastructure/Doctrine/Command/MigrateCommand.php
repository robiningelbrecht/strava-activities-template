<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Command;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Strava;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\Year;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationLoader;
use Doctrine\Migrations\DependencyFactory;
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
    private readonly Strava $strava;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigurationLoader $migrationConfig,
    ) {
        parent::__construct();
        $this->connectionFactory = $this->container->get(ConnectionFactory::class);
        $this->strava = $this->container->get(Strava::class);
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

        $io->info('Fetching Strava activities to determine databases to migrate...');

        $uniqueYears = array_unique(array_map(
            fn (array $activity) => Year::fromDate(SerializableDateTime::createFromFormat(
                Activity::DATE_TIME_FORMAT,
                $activity['start_date_local']
            )),
            $this->strava->getActivities()
        ));

        $connections = [
            $this->connectionFactory->getDefault(),
            $this->connectionFactory->getReadOnly(),
            ...array_map(
                fn (Year $year) => $this->connectionFactory->getForYear($year),
                $uniqueYears,
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
                $io->success(sprintf(
                    'Database "%s" already at the latest version ("%s")',
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

            $io->success(sprintf(
                'Successfully migrated database "%s" to version: %s',
                $databaseName,
                $version,
            ));
        }

        return Command::SUCCESS;
    }
}
