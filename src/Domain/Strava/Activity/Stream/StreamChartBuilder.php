<?php

namespace App\Domain\Strava\Activity\Stream;

final class StreamChartBuilder
{
    private bool $animation;
    private ?string $backgroundColor;

    private function __construct(
        private readonly ActivityStreamCollection $streams,
    ) {
        $this->animation = false;
        $this->backgroundColor = '#ffffff';
    }

    public static function fromStreams(ActivityStreamCollection $streams): self
    {
        return new self($streams);
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
            'backgroundColor' => $this->backgroundColor,
            'animation' => $this->animation,
            'legend' => [
                'show' => true,
            ],
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'toolbox' => [
                'feature' => [
                    'dataZoom' => [
                        'yAxisIndex' => 'none',
                    ],
                    'restore' => [
                    ],
                ],
            ],
            'dataZoom' => [
                [
                    'type' => 'inside',
                    'start' => 90,
                    'end' => 100,
                ],
                [
                    'start' => 90,
                    'end' => 100,
                ],
            ],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
            ],
            'yAxis' => [
                'type' => 'value',
            ],
            'series' => [
                [
                    'name' => 'Watts',
                    'type' => 'line',
                    'areaStyle' => [
                        'opacity' => 0.3,
                        'color' => 'rgb(250, 200, 88, 0.3)',
                    ],
                    'symbol' => 'none',
                    'sampling' => 'lttb',
                    'itemStyle' => [
                        'color' => '#FAC858',
                    ],
                    'data' => $this->streams->getByStreamType(StreamType::WATTS)->getData(),
                ],
                [
                    'type' => 'line',
                    'symbol' => 'none',
                    'sampling' => 'lttb',
                    'name' => 'Heartrate',
                    'color' => 'red',
                    'data' => $this->streams->getByStreamType(StreamType::HEART_RATE)->getData(),
                ],
                [
                    'type' => 'line',
                    'symbol' => 'none',
                    'name' => 'Cadence',
                    'sampling' => 'lttb',
                    'color' => 'rgb(84, 112, 198)',
                    'data' => $this->streams->getByStreamType(StreamType::CADENCE)->getData(),
                ],
            ],
        ];
    }
}
