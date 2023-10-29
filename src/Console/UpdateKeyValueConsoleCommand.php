<?php

declare(strict_types=1);

namespace App\Console;

use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:strava:key-value', description: 'Update KeyValue store')]
final class UpdateKeyValueConsoleCommand extends Command
{
    public function __construct(
        private readonly KeyValueStore $keyValueStore
    ) {
        parent::__construct();
    }

    public function configure()
    {
        parent::configure();

        $this->addArgument('key', InputArgument::REQUIRED);
        $this->addArgument('value', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = Key::from($input->getArgument('key'));
        $value = Value::fromString($input->getArgument('value'));

        $this->keyValueStore->save(KeyValue::fromState(
            key: $key,
            value: $value
        ));

        $output->writeln(sprintf('Value "%s" set for key "%s"', $value, $key->value));

        return Command::SUCCESS;
    }
}
