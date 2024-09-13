<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream;

final class PowerOutputChartBuilder
{
    private bool $animation;
    private ?string $backgroundColor;

    private function __construct(
        /** @var \App\Domain\Strava\Activity\Stream\PowerOutput[] */
        private readonly array $bestPowerOutputs,
    ) {
        $this->animation = false;
        $this->backgroundColor = '#ffffff';
    }

    /**
     * @param \App\Domain\Strava\Activity\Stream\PowerOutput[] $bestPowerOutputs
     */
    public static function fromBestPowerOutputs(
        array $bestPowerOutputs,
    ): self {
        return new self($bestPowerOutputs);
    }

    public function withAnimation(bool $flag): self
    {
        $this->animation = $flag;

        return $this;
    }

    public function withoutBackgroundColor(): self
    {
        $this->backgroundColor = null;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $powerOutputs = array_values(array_map(fn (PowerOutput $powerOutput) => $powerOutput->getPower(), $this->bestPowerOutputs));
        $yAxisOneMaxValue = ceil(max($powerOutputs) / 100) * 100;
        $yAxisOneInterval = $yAxisOneMaxValue / 5;

        $relativePowerOutputs = array_values(array_map(fn (PowerOutput $powerOutput) => $powerOutput->getRelativePower(), $this->bestPowerOutputs));
        $yAxisTwoMaxValue = ceil(max($relativePowerOutputs) / 5) * 5;
        $yAxisTwoInterval = $yAxisTwoMaxValue / 5;

        return [
            'animation' => $this->animation,
            'backgroundColor' => $this->backgroundColor,
            'color' => [
                '#E34902',
            ],
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'legend' => [
                'show' => true,
                'selectedMode' => false,
            ],
            'tooltip' => [
                'show' => true,
                'trigger' => 'axis',
                'formatter' => '<div style="width: 130px"><div style="display:flex;align-items:center;justify-content:space-between;"><div style="display:flex;align-items:center;column-gap:6px"><div style="border-radius:10px;width:10px;height:10px;background-color:#e34902"></div><div style="font-size:14px;color:#666;font-weight:400">Watt</div></div><div style="font-size:14px;color:#666;font-weight:900">{c0}</div></div><div style="display:flex;align-items:center;justify-content:space-between"><div style="display:flex;align-items:center;column-gap:6px"><div style="border-radius:10px;width:10px;height:10px;background-color:rgba(227,73,2,.7)"></div><div style="font-size:14px;color:#666;font-weight:400">Watt per kg</div></div><div style="font-size:14px;color:#666;font-weight:900">{c1}</div></div></div>',
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
                'data' => [
                    '1s',
                    '5s',
                    '',
                    '15s',
                    '',
                    '',
                    '1m',
                    '2m',
                    '',
                    '',
                    '5m',
                    '',
                    '8m',
                    '',
                    '',
                    '20m',
                    '30m',
                    '',
                    '',
                    '1h',
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'axisLabel' => [
                        'formatter' => '{value} w',
                    ],
                    'max' => $yAxisOneMaxValue,
                    'interval' => $yAxisOneInterval,
                ],
                [
                    'type' => 'value',
                    'axisLabel' => [
                        'formatter' => '{value} w/kg',
                    ],
                    'max' => $yAxisTwoMaxValue,
                    'interval' => $yAxisTwoInterval,
                ],
            ],
            'series' => [
                [
                    'type' => 'line',
                    'name' => 'Watt',
                    'smooth' => true,
                    'symbol' => 'none',
                    'yAxisIndex' => 0,
                    'data' => $powerOutputs,
                ],
                [
                    'type' => 'line',
                    'name' => 'Watt per kg',
                    'smooth' => true,
                    'symbol' => 'none',
                    'yAxisIndex' => 1,
                    'data' => $relativePowerOutputs,
                    'itemStyle' => [
                        'color' => 'rgba(227, 73, 2, 0.7)',
                    ],
                ],
            ],
        ];
    }
}
