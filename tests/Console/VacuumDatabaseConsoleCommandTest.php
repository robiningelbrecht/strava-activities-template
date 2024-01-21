<?php

namespace App\Tests\Console;

use App\Console\VacuumDatabaseConsoleCommand;
use App\Domain\Strava\StravaYears;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\ValueObject\Time\Year;
use App\Infrastructure\ValueObject\Time\YearCollection;
use App\Tests\ConsoleCommandTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class VacuumDatabaseConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private VacuumDatabaseConsoleCommand $vacuumDatabaseConsoleCommand;
    private MockObject $connectionFactory;
    private MockObject $stravaYears;
    private MockObject $filesystemOperator;

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

        $this->stravaYears
            ->expects($this->once())
            ->method('getYears')
            ->willReturn(YearCollection::fromArray([Year::fromInt(2023)]));

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

        $this->filesystemOperator
            ->expects($this->once())
            ->method('has')
            ->with('/database/db.strava-2023')
            ->willReturn(true);

        $result = $this->createMock(Result::class);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT COUNT(*) FROM Activity')
            ->willReturn($result);

        $result
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn('0');

        $this->filesystemOperator
            ->expects($this->once())
            ->method('delete')
            ->with('/database/db.strava-2023');

        $command = $this->getCommandInApplication('app:strava:vacuum');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesTextSnapshot($commandTester->getDisplay());
    }

    public function testExecuteWhenDbDoesNotExists(): void
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

        $this->stravaYears
            ->expects($this->once())
            ->method('getYears')
            ->willReturn(YearCollection::fromArray([Year::fromInt(2023)]));

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

        $this->filesystemOperator
            ->expects($this->once())
            ->method('has')
            ->with('/database/db.strava-2023')
            ->willReturn(false);

        $connection
            ->expects($this->never())
            ->method('executeQuery');

        $this->filesystemOperator
            ->expects($this->never())
            ->method('delete');

        $command = $this->getCommandInApplication('app:strava:vacuum');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testExecuteWhenDbIsNotEmpty(): void
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

        $this->stravaYears
            ->expects($this->once())
            ->method('getYears')
            ->willReturn(YearCollection::fromArray([Year::fromInt(2023)]));

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

        $this->filesystemOperator
            ->expects($this->once())
            ->method('has')
            ->with('/database/db.strava-2023')
            ->willReturn(true);

        $result = $this->createMock(Result::class);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT COUNT(*) FROM Activity')
            ->willReturn($result);

        $result
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn('3');

        $this->filesystemOperator
            ->expects($this->never())
            ->method('delete');

        $command = $this->getCommandInApplication('app:strava:vacuum');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionFactory = $this->createMock(ConnectionFactory::class);
        $this->stravaYears = $this->createMock(StravaYears::class);
        $this->filesystemOperator = $this->createMock(FilesystemOperator::class);

        $this->vacuumDatabaseConsoleCommand = new VacuumDatabaseConsoleCommand(
            $this->connectionFactory,
            $this->stravaYears,
            $this->filesystemOperator,
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->vacuumDatabaseConsoleCommand;
    }
}
