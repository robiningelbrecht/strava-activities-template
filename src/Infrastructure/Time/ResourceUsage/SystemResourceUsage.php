<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\ResourceUsage;

final class SystemResourceUsage implements ResourceUsage
{
    private ?float $timeStart = null;
    private ?float $timeStop = null;

    private const SIZES = [
        'GB' => 1073741824,
        'MB' => 1048576,
        'KB' => 1024,
    ];

    public function startTimer(): void
    {
        $this->timeStart = microtime(true);
        $this->timeStop = null;
    }

    public function stopTimer(): void
    {
        $this->timeStop = microtime(true);
    }

    /** @phpstan-impure */
    public function maxExecutionTimeReached(): bool
    {
        $timeElapsedInSeconds = (int) round(microtime(true) - $this->timeStart);

        // GitHub jobs shut down after six hours/
        // Let's terminate ours after 5.5 hours to be safe.
        return $timeElapsedInSeconds > 20000;
    }

    public function format(): string
    {
        return sprintf(
            'Time: %ss, Memory: %s, Peak Memory: %s',
            round($this->timeStop - $this->timeStart, 3),
            $this->bytesToString(memory_get_usage(true)),
            $this->bytesToString(memory_get_peak_usage(true))
        );
    }

    private function bytesToString(int $bytes): string
    {
        foreach (self::SIZES as $unit => $value) {
            if ($bytes >= $value) {
                return sprintf('%.2f %s', $bytes / $value, $unit);
            }
        }

        return $bytes.' byte'.(1 !== $bytes ? 's' : '');
    }
}
