<?php

namespace App\Tests\Console;

use App\Console\VacuumDatabaseConsoleCommand;
use App\Domain\Strava\Activity\WriteModel\ActivityRepository;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\ValueObject\Time\Year;
use App\Tests\ConsoleCommandTestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class VacuumDatabaseConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private VacuumDatabaseConsoleCommand $vacuumDatabaseConsoleCommand;
    private MockObject $connectionFactory;
    private MockObject $activityRepository;

    public function testExecute(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->connectionFactory
            ->expects($this->once())
            ->method('getDefault')
            ->willReturn($connection);

        $connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('VACUUM');

        $this->activityRepository
            ->expects($this->once())
            ->method('findUniqueYears')
            ->willReturn([Year::fromInt(2023)]);

        $connection = $this->createMock(Connection::class);
        $this->connectionFactory
            ->expects($this->once())
            ->method('getForYear')
            ->with(Year::fromInt(2023))
            ->willReturn($connection);

        $connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('VACUUM');

        $command = $this->getCommandInApplication('app:strava:vacuum');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesTextSnapshot($commandTester->getDisplay());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionFactory = $this->createMock(ConnectionFactory::class);
        $this->activityRepository = $this->createMock(ActivityRepository::class);

        $this->vacuumDatabaseConsoleCommand = new VacuumDatabaseConsoleCommand(
            $this->connectionFactory,
            $this->activityRepository
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->vacuumDatabaseConsoleCommand;
    }
}
