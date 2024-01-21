<?php

namespace App\Tests\Console;

use App\Console\ImportStravaDataConsoleCommand;
use App\Domain\Strava\MaxResourceUsageHasBeenReached;
use App\Infrastructure\CQRS\CommandBus;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use App\Tests\ConsoleCommandTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ImportStravaDataConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private ImportStravaDataConsoleCommand $importStravaDataConsoleCommand;
    private MockObject $commandBus;
    private MockObject $maxResourceUsageHasBeenReached;
    private MockObject $resourceUsage;

    public function testExecute(): void
    {
        $this->maxResourceUsageHasBeenReached
            ->expects($this->once())
            ->method('clear');

        $this->resourceUsage
            ->expects($this->exactly(2))
            ->method('maxExecutionTimeReached')
            ->willReturn(false);

        $this->maxResourceUsageHasBeenReached
            ->expects($this->never())
            ->method('hasReached');

        $this->commandBus
            ->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(fn (DomainCommand $command) => $this->assertMatchesJsonSnapshot(Json::encode($command)));

        $command = $this->getCommandInApplication('app:strava:import-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testExecuteMaxExecutionTimeReachedOne(): void
    {
        $this->maxResourceUsageHasBeenReached
            ->expects($this->once())
            ->method('clear');

        $this->resourceUsage
            ->expects($this->once())
            ->method('maxExecutionTimeReached')
            ->willReturn(true);

        $this->maxResourceUsageHasBeenReached
            ->expects($this->once())
            ->method('markAsReached');

        $this->commandBus
            ->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(fn (DomainCommand $command) => $this->assertMatchesJsonSnapshot(Json::encode($command)));

        $command = $this->getCommandInApplication('app:strava:import-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testExecuteMaxExecutionTimeReachedTwo(): void
    {
        $this->maxResourceUsageHasBeenReached
            ->expects($this->once())
            ->method('clear');

        $matcher = $this->exactly(2);
        $this->resourceUsage
            ->expects($matcher)
            ->method('maxExecutionTimeReached')
            ->willReturnCallback(function () use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    return false;
                }

                return true;
            });

        $this->maxResourceUsageHasBeenReached
            ->expects($this->once())
            ->method('markAsReached');

        $this->commandBus
            ->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(fn (DomainCommand $command) => $this->assertMatchesJsonSnapshot(Json::encode($command)));

        $command = $this->getCommandInApplication('app:strava:import-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBus::class);
        $this->maxResourceUsageHasBeenReached = $this->createMock(MaxResourceUsageHasBeenReached::class);
        $this->resourceUsage = $this->createMock(ResourceUsage::class);

        $this->importStravaDataConsoleCommand = new ImportStravaDataConsoleCommand(
            $this->commandBus,
            $this->maxResourceUsageHasBeenReached,
            $this->resourceUsage
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->importStravaDataConsoleCommand;
    }
}
