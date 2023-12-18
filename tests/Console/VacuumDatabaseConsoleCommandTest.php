<?php

namespace App\Tests\Console;

use App\Console\VacuumDatabaseConsoleCommand;
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
    private MockObject $connection;

    public function testExecute(): void
    {
        $this->connection
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

        $this->connection = $this->createMock(Connection::class);

        $this->vacuumDatabaseConsoleCommand = new VacuumDatabaseConsoleCommand(
            $this->connection
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->vacuumDatabaseConsoleCommand;
    }
}
