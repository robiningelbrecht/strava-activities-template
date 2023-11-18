<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

final readonly class PowerDistributionChartBuilder
{
    private function __construct(
        /** @var array<int, int> */
        private array $powerData,
        private int $averagePower,
    ) {
    }

    /**
     * @param array<int, int> $powerData
     */
    public static function fromPowerData(
        array $powerData,
        int $averagePower,
    ): self {
        return new self(
            powerData: $powerData,
            averagePower: $averagePower,
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        // Calculate all data related things.
        $powerData = $this->powerData;
        $powers = array_keys($powerData);
        $minPower = 0;
        $maxPower = (int) ceil(max($powers) / 100) * 100;

        foreach (range($minPower, $maxPower) as $power) {
            if (array_key_exists($power, $this->powerData)) {
                continue;
            }
            $powerData[$power] = 0;
        }
        ksort($powerData);

        $step = (int) floor(($maxPower - $minPower) / 24) ?: 1;
        $xAxisValues = range($minPower, $maxPower, $step);
        if (end($xAxisValues) < $maxPower) {
            $xAxisValues[] = $maxPower;
        }

        $totalTimeInSeconds = array_sum($powerData);
        $data = [];
        foreach ($xAxisValues as $axisValue) {
            $data[] = array_sum(array_splice($powerData, 0, $step)) / $totalTimeInSeconds * 100;
        }
        $yAxisMax = max($data) * 1.2;
        $xAxisValueAveragePower = array_search(floor($this->averagePower / $step) * $step, $xAxisValues);

        return [
            'grid' => [
                'left' => '1%',
                'right' => '1%',
                'bottom' => '7%',
                'height' => '325px',
                'containLabel' => false,
            ],
            'xAxis' => [
                'type' => 'category',
                'data' => $xAxisValues,
                'axisTick' => [
                    'show' => false,
                ],
                'axisLine' => [
                    'show' => false,
                ],
                'axisLabel' => [
                    'interval' => 2,
                    'showMinLabel' => true,
                    // 'showMaxLabel' => true,
                ],
            ],
            'yAxis' => [
                'show' => false,
                'min' => 0,
                'max' => $yAxisMax,
            ],
            'series' => [
                [
                    'data' => $data,
                    'type' => 'bar',
                    'barCategoryGap' => 1,
                    'itemStyle' => [
                        'color' => '#fff',
                        'borderRadius' => [25, 25, 0, 0],
                    ],
                    'markPoint' => [
                        'silent' => true,
                        'animation' => false,
                        'symbolSize' => 45,
                        'symbol' => 'roundRect',
                        'itemStyle' => [
                            'color' => '#7F7F7F',
                        ],
                        'label' => [
                            'formatter' => "{label|AVG}\n{sub|{c}}",
                            'lineHeight' => 15,
                            'rich' => [
                                'label' => [
                                    'fontWeight' => 'bold',
                                ],
                                'sub' => [
                                    'fontSize' => 12,
                                ],
                            ],
                        ],
                        'data' => [
                            [
                                'value' => $this->averagePower,
                                'coord' => [$xAxisValueAveragePower, $yAxisMax * 0.9],
                            ],
                        ],
                    ],
                    'markLine' => [
                        'symbol' => 'none',
                        'animation' => false,
                        'lineStyle' => [
                            'type' => 'solid',
                            'width' => 2,
                            'color' => '#7F7F7F',
                        ],
                        'label' => [
                            'show' => false,
                        ],
                        'data' => [
                            [
                                ['xAxis' => $xAxisValueAveragePower, 'yAxis' => 0],
                                ['xAxis' => $xAxisValueAveragePower, 'yAxis' => $yAxisMax * 0.9],
                            ],
                        ],
                        'silent' => true,
                    ],
                    'markArea' => [
                        'data' => [
                            [
                                [
                                    'itemStyle' => [
                                        'color' => '#303030',
                                    ],
                                ],
                                [
                                    'x' => '100%',
                                ],
                            ],
                        ],
                        'silent' => true,
                    ],
                ],
            ],
        ];
    }
}
