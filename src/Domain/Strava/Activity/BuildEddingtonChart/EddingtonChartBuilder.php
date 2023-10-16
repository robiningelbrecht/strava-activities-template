<?php

namespace App\Domain\Strava\Activity\BuildEddingtonChart;

final class EddingtonChartBuilder
{
    private bool $animation;
    private ?string $backgroundColor;

    private function __construct(
        private readonly Eddington $eddington
    ) {
        $this->animation = false;
        $this->backgroundColor = '#ffffff';
    }

    public static function fromEddington(Eddington $eddington): self
    {
        return new self($eddington);
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
        $longestDistanceInADay = $this->eddington->getLongestDistanceInADay();
        $timesCompletedData = $this->eddington->getTimesCompletedData();
        $eddingtonNumber = $this->eddington->getNumber();

        $yAxisMaxValue = ceil(max($timesCompletedData) / 30) * 30;

        $timesCompletedDataForChart = $timesCompletedData;
        $timesCompletedDataForChart[$eddingtonNumber] = [
            'value' => $timesCompletedData[$eddingtonNumber],
            'itemStyle' => [
                'color' => 'rgba(227, 73, 2, 0.8)',
            ],
        ];

        return [
            'backgroundColor' => $this->backgroundColor,
            'animation' => $this->animation,
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'legend' => [
                'show' => true,
            ],
            'xAxis' => [
                'data' => array_map(fn (int $distance) => $distance.'km', range(1, $longestDistanceInADay)),
                'type' => 'category',
                'axisTick' => [
                    'alignWithLabel' => true,
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => true,
                    ],
                    'max' => $yAxisMaxValue,
                    'interval' => 30,
                ],
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'max' => $yAxisMaxValue,
                    'interval' => 30,
                ],
            ],
            'series' => [
                [
                    'name' => 'Times completed',
                    'yAxisIndex' => 0,
                    'type' => 'bar',
                    'label' => [
                        'show' => false,
                    ],
                    'showBackground' => false,
                    'itemStyle' => [
                        'color' => 'rgba(227, 73, 2, 0.3)',
                    ],
                    'markPoint' => [
                        'symbol' => 'pin',
                        'symbolOffset' => [
                            0,
                            -5,
                        ],
                        'itemStyle' => [
                            'color' => 'rgba(227, 73, 2, 0.8)',
                        ],
                        'data' => [
                            [
                                'value' => $eddingtonNumber,
                                'coord' => [
                                    $eddingtonNumber - 1,
                                    $timesCompletedData[$eddingtonNumber] - 1,
                                ],
                            ],
                        ],
                    ],
                    'data' => array_values($timesCompletedDataForChart),
                ],
                [
                    'name' => 'Eddington',
                    'yAxisIndex' => 1,
                    'zlevel' => 1,
                    'type' => 'line',
                    'smooth' => false,
                    'showSymbol' => false,
                    'label' => [
                        'show' => false,
                    ],
                    'showBackground' => false,
                    'itemStyle' => [
                        'color' => '#E34902',
                    ],
                    'data' => range(1, $longestDistanceInADay),
                ],
            ],
        ];
    }
}
