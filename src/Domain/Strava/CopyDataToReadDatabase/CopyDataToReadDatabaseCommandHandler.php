<?php

declare(strict_types=1);

namespace App\Domain\Strava\CopyDataToReadDatabase;

use App\Domain\Strava\StravaYears;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Environment\Settings;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;

#[AsCommandHandler]
final readonly class CopyDataToReadDatabaseCommandHandler implements CommandHandler
{
    private AbstractSchemaManager $schemaManager;
    private Connection $readOnlyConnection;

    public function __construct(
        ConnectionFactory $connectionFactory,
        private Settings $settings,
        private StravaYears $stravaYears
    ) {
        $this->readOnlyConnection = $connectionFactory->getReadOnly();
        $this->schemaManager = $connectionFactory->getDefault()->createSchemaManager();
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof CopyDataToReadDatabase);
        $command->getOutput()->writeln('Copying data to read database...');

        // Make sure the read DB is empty
        foreach ($this->schemaManager->listTableNames() as $tableName) {
            if ('doctrine_migration_versions' === $tableName) {
                continue; // @codeCoverageIgnore
            }
            $this->readOnlyConnection->executeStatement('DELETE FROM '.$tableName);
        }

        // First copy data from the default database.
        $this->copyData(
            databaseToAttach: $this->settings->get('doctrine.connections.default.path'),
            tablesToCopy: ['Ftp', 'Gear', 'KeyValue', 'Segment']
        );

        // Copy all activity related data from yearly databases.
        foreach ($this->stravaYears->getYears() as $year) {
            $this->copyData(
                databaseToAttach: str_replace('%YEAR%', (string) $year, $this->settings->get('doctrine.connections.year_based.path')),
                tablesToCopy: ['Activity', 'ActivityStream', 'Challenge', 'SegmentEffort']
            );
        }
    }

    /**
     * @param string[] $tablesToCopy
     */
    private function copyData(string $databaseToAttach, array $tablesToCopy): void
    {
        $this->readOnlyConnection->executeStatement("ATTACH DATABASE :attachedDatabase as 'attachedDatabase'", [
            'attachedDatabase' => $databaseToAttach,
        ]);

        foreach ($tablesToCopy as $tableName) {
            $columnNames = array_map(
                fn (Column $column) => $column->getName(),
                $this->schemaManager->listTableColumns($tableName)
            );
            $this->readOnlyConnection->executeStatement(sprintf(
                'INSERT INTO main.%s (%s) SELECT %s FROM attachedDatabase.%s',
                $tableName,
                implode(',', $columnNames),
                implode(',', $columnNames),
                $tableName,
            ));
        }
        $this->readOnlyConnection->executeStatement("DETACH DATABASE 'attachedDatabase'");
    }
}
