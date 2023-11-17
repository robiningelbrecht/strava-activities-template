<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

final readonly class HeartRateDistributionChartBuilder
{
    private function __construct()
    {
    }

    public static function fromHeartRateData(): self
    {
        return new self();
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        return [
            'grid' => [
                'left' => '1%',
                'right' => '1%',
                'bottom' => '7%',
                'height' => '375px',
                'containLabel' => false,
            ],
            'xAxis' => [
                'type' => 'category',
                'data' => [60, 64, 68, 72, 76, 80, 84, 88, 92, 96, 100, 104, 108, 112, 116, 120, 124, 128, 132, 136, 140, 144, 148, 152, 156, 160, 164, 168, 172, 176, 180, 184, 188, 192, 196, 200],
                'axisTick' => [
                    'show' => false,
                ],
                'axisLine' => [
                    'show' => false,
                ],
                'axisLabel' => [
                    'interval' => 2,
                    'showMinLabel' => true,
                    'showMaxLabel' => true,
                ],
            ],
            'yAxis' => [
                'show' => false,
                'min' => 0,
                'max' => 120,
            ],
            'series' => [
                [
                    'data' => [84, 22, 38, 40, 5, 63, 12, 7, 53, 12, 65, 38, 22, 52, 63, 1, 47, 85, 84, 68, 79, 92, 40, 49, 48, 97, 100, 10, 62, 28, 8, 36, 6, 18, 95, 31, 47, 91, 31, 94, 27, 88, 50, 35, 47, 46, 28, 40, 46, 29, 17, 45, 79, 52, 66, 86, 8, 51, 87, 84, 49, 13, 61, 65, 4, 55, 32, 9, 36, 35, 54, 8, 92, 57, 57, 52, 3, 82, 31, 54, 11, 6, 4, 40, 86, 85, 46, 89, 82, 62, 42, 83, 4, 99, 38, 61, 77, 77, 68, 1, 2, 3, 59, 93, 67, 82, 36, 46, 42, 94, 70, 19, 70, 39, 48, 19, 7, 74, 45, 26, 80, 94, 8, 91, 99, 4, 18, 57, 24, 57, 50, 87, 65, 16, 18, 23, 48, 47, 90, 71, 35],
                    'type' => 'bar',
                    'barCategoryGap' => 1,
                    'itemStyle' => [
                        'color' => '#fff',
                        'borderRadius' => [25, 25, 0, 0],
                    ],
                    'markPoint' => [
                        'silent' => true,
                        'animation' => false,
                        'symbol' => 'path://M2 9.1371C2 14 6.01943 16.5914 8.96173 18.9109C10 19.7294 11 20.5 12 20.5C13 20.5 14 19.7294 15.0383 18.9109C17.9806 16.5914 22 14 22 9.1371C22 4.27416 16.4998 0.825464 12 5.50063C7.50016 0.825464 2 4.27416 2 9.1371Z',
                        'symbolSize' => [55, 48],
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
                                'value' => 120,
                                'coord' => [20, 100],
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
                                ['xAxis' => 20, 'yAxis' => 0],
                                ['xAxis' => 20, 'yAxis' => 100],
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
                                    'x' => '23.1%',
                                ],
                            ],
                            [
                                [
                                    'name' => "Zone 1\n50% - 60%",
                                    'label' => [
                                        'position' => 'insideTop',
                                        'fontWeight' => 'bold',
                                        'fontSize' => 10,
                                        'textAlign' => 'center',
                                        'distance' => 12,
                                        'textBorderColor' => 'none',
                                    ],
                                    'itemStyle' => [
                                        'color' => '#DF584A',
                                    ],
                                    'x' => '23.1%',
                                ],
                                [
                                    'x' => '36.24%',
                                ],
                            ],
                            [
                                [
                                    'name' => "Zone 2\n60% - 70%",
                                    'label' => [
                                        'position' => 'insideTop',
                                        'fontWeight' => 'bold',
                                        'fontSize' => 10,
                                        'textAlign' => 'center',
                                        'distance' => 12,
                                        'textBorderColor' => 'none',
                                    ],
                                    'itemStyle' => [
                                        'color' => '#D63522',
                                    ],
                                    'x' => '36.24%',
                                ],
                                [
                                    'x' => '49.38%',
                                ],
                            ],
                            [
                                [
                                    'name' => "Zone 3\n70% - 80%",
                                    'label' => [
                                        'position' => 'insideTop',
                                        'fontWeight' => 'bold',
                                        'fontSize' => 10,
                                        'textAlign' => 'center',
                                        'distance' => 12,
                                        'textBorderColor' => 'none',
                                    ],
                                    'itemStyle' => [
                                        'color' => '#BD2D22',
                                    ],
                                    'x' => '49.38%',
                                ],
                                [
                                    'x' => '62.52%',
                                ],
                            ],
                            [
                                [
                                    'name' => "Zone 4\n80% - 90%",
                                    'label' => [
                                        'position' => 'insideTop',
                                        'fontWeight' => 'bold',
                                        'fontSize' => 10,
                                        'textAlign' => 'center',
                                        'distance' => 12,
                                        'textBorderColor' => 'none',
                                    ],
                                    'itemStyle' => [
                                        'color' => '#942319',
                                    ],
                                    'x' => '62.52%',
                                ],
                                [
                                    'x' => '75.66%',
                                ],
                            ],
                            [
                                [
                                    'name' => "Zone 5\n> 90%",
                                    'label' => [
                                        'position' => 'insideTop',
                                        'fontWeight' => 'bold',
                                        'fontSize' => 10,
                                        'textAlign' => 'center',
                                        'distance' => 12,
                                        'textBorderColor' => 'none',
                                    ],
                                    'itemStyle' => [
                                        'color' => '#6A1009',
                                    ],
                                    'x' => '75.66%',
                                ],
                                [
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
