<?php

namespace App\Tests\Console;

use App\Console\UpdateKeyValueConsoleCommand;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Tests\ConsoleCommandTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateKeyValueConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private UpdateKeyValueConsoleCommand $updateKeyValueConsoleCommand;
    private MockObject $keyValueStore;

    public function testExecute(): void
    {
        $this->keyValueStore
            ->expects($this->once())
            ->method('save')
            ->with(KeyValue::fromState(
                key: Key::ATHLETE_BIRTHDAY,
                value: Value::fromString('1989-08-14')
            ));

        $command = $this->getCommandInApplication('app:strava:key-value');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'key' => Key::ATHLETE_BIRTHDAY->value,
            'value' => '1989-08-14',
        ]);

        $this->assertMatchesTextSnapshot($commandTester->getDisplay());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyValueStore = $this->createMock(KeyValueStore::class);
        $this->updateKeyValueConsoleCommand = new UpdateKeyValueConsoleCommand(
            $this->keyValueStore
        );
    }

    protected function getConsoleCommand(): Command
    {
        return $this->updateKeyValueConsoleCommand;
    }
}
