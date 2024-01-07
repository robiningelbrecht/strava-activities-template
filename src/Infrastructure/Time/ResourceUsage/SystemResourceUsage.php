<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\ResourceUsage;

final readonly class SystemResourceUsage implements ResourceUsage
{
    private const SIZES = [
        'GB' => 1073741824,
        'MB' => 1048576,
        'KB' => 1024,
    ];

    public function format(float $durationInMicroSeconds): string
    {
        return sprintf(
            'Time: %ss, Memory: %s',
            $durationInMicroSeconds / 1000,
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
