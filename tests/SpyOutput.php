<?php

namespace App\Tests;

use Symfony\Component\Console\Output\BufferedOutput;

class SpyOutput extends BufferedOutput implements \Stringable
{
    private array $messages = [];

    public function writeln($messages, int $options = self::OUTPUT_NORMAL): void
    {
        if (!is_iterable($messages)) {
            $messages = [$messages];
        }
        $this->messages = [...$this->messages, ...$messages];
    }

    public function write($messages, bool $newline = false, int $options = self::OUTPUT_NORMAL): void
    {
        if (!is_iterable($messages)) {
            $messages = [$messages];
        }
        $this->messages = [...$this->messages, ...$messages];
    }

    public function __toString(): string
    {
        return implode(PHP_EOL, $this->messages);
    }
}
