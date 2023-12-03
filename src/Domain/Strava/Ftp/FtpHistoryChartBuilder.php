<?php

declare(strict_types=1);

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class FtpHistoryChartBuilder
{
    private bool $animation;
    private ?string $backgroundColor;

    private function __construct(
        private readonly FtpCollection $ftps,
        private readonly SerializableDateTime $now,
    ) {
        $this->animation = false;
        $this->backgroundColor = '#ffffff';
    }

    public static function fromFtps(
        FtpCollection $ftps,
        SerializableDateTime $now
    ): self {
        return new self(
            ftps: $ftps,
            now: $now
        );
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
        return [
            'animation' => $this->animation,
            'backgroundColor' => $this->backgroundColor,
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'time',
                    'boundaryGap' => false,
                    'axisTick' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => [
                            'year' => '{yyyy}',
                            'month' => '{MMM}',
                            'day' => '',
                            'hour' => '{HH}:{mm}',
                            'minute' => '{HH}:{mm}',
                            'second' => '{HH}:{mm}:{ss}',
                            'millisecond' => '{hh}:{mm}:{ss} {SSS}',
                            'none' => '{yyyy}-{MM}-{dd}',
                        ],
                    ],
                    'splitLine' => [
                        'show' => true,
                        'lineStyle' => [
                            'color' => '#E0E6F1',
                        ],
                    ],
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} w',
                    ],
                ],
                [
                    'type' => 'value',
                    'splitLine' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'formatter' => '{value} w/kg',
                    ],
                ],
            ],
            'series' => [
                [
                    'name' => 'FTP watts',
                    'color' => [
                        '#E34902',
                    ],
                    'type' => 'line',
                    'smooth' => false,
                    'yAxisIndex' => 0,
                    'label' => [
                        'show' => false,
                    ],
                    'lineStyle' => [
                        'width' => 1,
                    ],
                    'symbolSize' => 6,
                    'showSymbol' => true,
                    'data' => [
                        ...$this->ftps->map(
                            fn (Ftp $ftp) => [
                                $ftp->getSetOn()->format('Y-m-d'),
                                $ftp->getFtp(),
                            ],
                        ),
                        $this->ftps->getLast() && $this->now->format('Y-m-d') != $this->ftps->getLast()->getSetOn()->format('Y-m-d') ?
                        [
                            $this->now->format('Y-m-d'),
                            $this->ftps->getLast()->getFtp(),
                        ] : [],
                    ],
                ],
                [
                    'name' => 'FTP w/kg',
                    'type' => 'line',
                    'smooth' => false,
                    'color' => [
                        '#3AA272',
                    ],
                    'yAxisIndex' => 1,
                    'label' => [
                        'show' => false,
                    ],
                    'lineStyle' => [
                        'width' => 1,
                    ],
                    'symbolSize' => 6,
                    'showSymbol' => true,
                    'data' => [
                        ...$this->ftps->map(
                            fn (Ftp $ftp) => [
                                $ftp->getSetOn()->format('Y-m-d'),
                                $ftp->getRelativeFtp(),
                            ]
                        ),
                        $this->ftps->getLast() && $this->now->format('Y-m-d') != $this->ftps->getLast()->getSetOn()->format('Y-m-d') ?
                        [
                            $this->now->format('Y-m-d'),
                            $this->ftps->getLast()->getRelativeFtp(),
                        ] : [],
                    ],
                ],
            ],
        ];
    }
}
