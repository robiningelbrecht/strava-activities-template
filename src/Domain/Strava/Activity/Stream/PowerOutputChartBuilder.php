<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

final readonly class PowerOutputChartBuilder
{
    private function __construct(
        /** @var \App\Domain\Strava\Activity\Stream\PowerOutput[] */
        private array $bestPowerOutputs,
    ) {
    }

    /**
     * @param \App\Domain\Strava\Activity\Stream\PowerOutput[] $bestPowerOutputs
     */
    public static function fromBestPowerOutputs(
        array $bestPowerOutputs
    ): self {
        return new self($bestPowerOutputs);
    }

    public function build(): array
    {
        return [
            'tooltip' => [
                'show' => true,
                'trigger' => 'axis',
                'formatter' => '{c} w',
            ],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
                'axisLabel' => [
                    'interval' => 0,
                ],
                'axisTick' => [
                    'show' => false,
                ],
                'data' => array_values(array_map(fn (PowerOutput $powerOutput) => str_replace(' ', '', $powerOutput->getTime()), $this->bestPowerOutputs)),
            ],
            'yAxis' => [
                'type' => 'value',
                'axisLabel' => [
                    'formatter' => '{value} w',
                ],
            ],
            'series' => [
                [
                    'type' => 'line',
                    'smooth' => true,
                    'symbol' => 'none',
                    'data' => array_values(array_map(fn (PowerOutput $powerOutput) => $powerOutput->getPower(), $this->bestPowerOutputs)),
                ],
            ],
        ];
    }
}
