<?php

namespace App\Tests\Console;

use App\Console\BuildStravaActivityFilesConsoleCommand;
use App\Domain\Strava\ReachedStravaApiRateLimits;
use App\Infrastructure\CQRS\CommandBus;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Serialization\Json;
use App\Tests\ConsoleCommandTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class BuildStravaActivityFilesConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private BuildStravaActivityFilesConsoleCommand $buildStravaActivityFilesConsoleCommand;
    private MockObject $commandBus;
    private MockObject $reachedStravaApiRateLimits;

    public function testExecute(): void
    {
        $this->reachedStravaApiRateLimits
            ->expects($this->once())
            ->method('hasReached')
            ->willReturn(false);

        $this->commandBus
            ->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(fn (DomainCommand $command) => $this->assertMatchesJsonSnapshot(Json::encode($command)));

        $command = $this->getCommandInApplication('app:strava:build-files');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    public function testExecuteWhenStravaLimitsHaveBeenReahced(): void
    {
        $this->reachedStravaApiRateLimits
            ->expects($this->once())
            ->method('hasReached')
            ->willReturn(true);

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $command = $this->getCommandInApplication('app:strava:build-files');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBus::class);
        $this->reachedStravaApiRateLimits = $this->createMock(ReachedStravaApiRateLimits::class);

        $this->buildStravaActivityFilesConsoleCommand = new BuildStravaActivityFilesConsoleCommand(
            $this->commandBus,
            $this->reachedStravaApiRateLimits
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->buildStravaActivityFilesConsoleCommand;
    }
}
