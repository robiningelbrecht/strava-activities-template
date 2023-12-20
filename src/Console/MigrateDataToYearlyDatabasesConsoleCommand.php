<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Strava\StravaYears;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Environment\Settings;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @codeCoverageIgnore
 *
 * @deprecated Remove when executed
 */
#[AsCommand(name: 'app:strava:migrate-to-yearly-database', description: 'Migrate data to yearly databases')]
class MigrateDataToYearlyDatabasesConsoleCommand extends Command
{
    public function __construct(
        private readonly ConnectionFactory $connectionFactory,
        private readonly StravaYears $stravaYears
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connection = $this->connectionFactory->getDefault();

        if (0 === $connection->executeQuery('SELECT COUNT(*) FROM Activity')->fetchOne()) {
            $io->success('Data has already been migrated');

            return Command::SUCCESS;
        }

        $uniqueYears = $this->stravaYears->getYears();
        foreach ($uniqueYears as $year) {
            $connection = $this->connectionFactory->getDefault();
            $connection->executeStatement('BEGIN TRANSACTION');
            try {
                $connection->executeStatement("ATTACH DATABASE :yearlyDatabase as 'yearlyDatabase'", [
                    'yearlyDatabase' => Settings::getAppRoot().'/'.$_ENV['DATABASE_NAME'].'-'.$year,
                ]);

                // -------| Migrate activity streams |-------
                $query = <<<SQL
                INSERT INTO yearlyDatabase.ActivityStream (activityId, streamType, createdOn, data) 
                SELECT ActivityStream.activityId, streamType, Activity.startDateTime, ActivityStream.data FROM main.ActivityStream 
                INNER JOIN main.Activity ON main.ActivityStream.activityId = main.Activity.activityId 
                WHERE startDateTime LIKE :year
                SQL;

                $connection->executeStatement($query, [
                    'year' => $year.'-%',
                ]);

                $query = <<<SQL
                DELETE FROM main.ActivityStream
                WHERE main.ActivityStream.activityId IN(
                    SELECT main.Activity.activityId
                    FROM main.Activity
                    WHERE main.Activity.startDateTime LIKE :year
                )
                SQL;

                $connection->executeStatement($query, [
                    'year' => $year.'-%',
                ]);

                // -------| Migrate challenges |-------
                $connection->executeStatement('INSERT INTO yearlyDatabase.Challenge (challengeId, createdOn, data) SELECT challengeId, createdOn, data FROM main.Challenge WHERE challengeId LIKE :year', [
                    'year' => $year.'-%',
                ]);
                $connection->executeStatement('DELETE FROM main.Challenge WHERE challengeId LIKE :year', [
                    'year' => $year.'-%',
                ]);

                // -------| Migrate segment efforts |-------
                $connection->executeStatement('INSERT INTO yearlyDatabase.SegmentEffort (segmentEffortId, segmentId, activityId, startDateTime, data) SELECT segmentEffortId, segmentId, activityId, startDateTime, data FROM main.SegmentEffort WHERE startDateTime LIKE :year', [
                    'year' => $year.'-%',
                ]);
                $connection->executeStatement('DELETE FROM main.SegmentEffort WHERE startDateTime LIKE :year', [
                    'year' => $year.'-%',
                ]);

                // -------| Migrate activities |-------
                $connection->executeStatement('INSERT INTO yearlyDatabase.Activity (activityId, startDateTime, data, gearId, weather) SELECT activityId, startDateTime, data, gearId, weather FROM main.Activity WHERE startDateTime LIKE :year', [
                    'year' => $year.'-%',
                ]);
                $connection->executeStatement('DELETE FROM main.Activity WHERE startDateTime LIKE :year', [
                    'year' => $year.'-%',
                ]);

                $connection->executeStatement('COMMIT');
                $io->success(sprintf('Data for year %s successfully migrated', $year));
            } catch (\Exception $e) {
                $connection->executeStatement('ROLLBACK');
                $io->error(sprintf('Data for year %s could not be migrated: %s', $year, $e->getMessage()));
            }
        }

        return Command::SUCCESS;
    }
}
