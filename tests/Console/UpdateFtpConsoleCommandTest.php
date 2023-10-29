<?php

namespace App\Tests\Console;

use App\Console\UpdateFtpConsoleCommand;
use App\Domain\Strava\Ftp\Ftp;
use App\Domain\Strava\Ftp\FtpRepository;
use App\Domain\Strava\Ftp\FtpValue;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ConsoleCommandTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateFtpConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private UpdateFtpConsoleCommand $updateFtpConsoleCommand;
    private MockObject $ftpRepository;

    public function testExecute(): void
    {
        $this->ftpRepository
            ->expects($this->once())
            ->method('save')
            ->with(Ftp::fromState(
                setOn: SerializableDateTime::fromString('2023-04-23'),
                ftp: FtpValue::fromInt(250)
            ));

        $command = $this->getCommandInApplication('app:strava:update-ftp');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'ftp' => 250,
            'setOn' => '2023-04-23',
        ]);

        $this->assertMatchesTextSnapshot($commandTester->getDisplay());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ftpRepository = $this->createMock(FtpRepository::class);

        $this->updateFtpConsoleCommand = new UpdateFtpConsoleCommand(
            $this->ftpRepository
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->updateFtpConsoleCommand;
    }
}
