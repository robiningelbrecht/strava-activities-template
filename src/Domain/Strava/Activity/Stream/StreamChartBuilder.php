<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Infrastructure\Time\TimeFormatter;

final class StreamChartBuilder
{
    use TimeFormatter;

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
        $wattStream = $this->streams->getByStreamType(StreamType::WATTS)?->getData() ?? [];
        $hearRateStream = $this->streams->getByStreamType(StreamType::HEART_RATE)?->getData() ?? [];
        $cadenceStream = $this->streams->getByStreamType(StreamType::CADENCE)?->getData() ?? [];

        $numberOfDataPointsOnXAxis = min([count($wattStream), count($hearRateStream), count($cadenceStream)]);
        $xAxisValues = array_map(fn (int $timeInSeconds) => $this->formatDurationForHumans($timeInSeconds, false), range(0, $numberOfDataPointsOnXAxis));

        return [
            'backgroundColor' => $this->backgroundColor,
            'animation' => $this->animation,
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'containLabel' => true,
            ],
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
                    'start' => 0,
                    'end' => 100,
                ],
                [
                    'start' => 0,
                    'end' => 100,
                ],
            ],
            'xAxis' => [
                'type' => 'category',
                'boundaryGap' => false,
                'data' => $xAxisValues,
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
                    'data' => array_slice($wattStream, 0, $numberOfDataPointsOnXAxis),
                ],
                [
                    'type' => 'line',
                    'symbol' => 'none',
                    'sampling' => 'lttb',
                    'name' => 'Heartrate',
                    'color' => 'red',
                    'data' => array_slice($hearRateStream, 0, $numberOfDataPointsOnXAxis),
                ],
                [
                    'type' => 'line',
                    'symbol' => 'none',
                    'name' => 'Cadence',
                    'sampling' => 'lttb',
                    'color' => 'rgb(84, 112, 198)',
                    'data' => array_slice($cadenceStream, 0, $numberOfDataPointsOnXAxis),
                ],
            ],
        ];
    }
}
