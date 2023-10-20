<?php

namespace App\Domain\Strava\Activity\BuildWeeklyDistanceChart;

use App\Domain\Strava\Activity\ActivityCollection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class WeeklyDistanceChartBuilder
{
    private bool $animation;
    private ?string $backgroundColor;

    private function __construct(
        private readonly ActivityCollection $activities,
        private readonly SerializableDateTime $now,
    ) {
        $this->animation = false;
        $this->backgroundColor = '#ffffff';
    }

    public static function fromActivities(ActivityCollection $activities, SerializableDateTime $now): self
    {
        return new self($activities, $now);
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
            'color' => [
                '#E34902',
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
                            'none' => '{yyyy}-{MM}-{dd} {hh}:{mm}:{ss} {SSS}',
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
                        'formatter' => '{value} km',
                    ],
                ],
            ],
            'series' => [
                [
                    'name' => 'Average distance / week',
                    'type' => 'line',
                    'smooth' => false,
                    'label' => [
                        'show' => true,
                        'formatter' => '{@[1]} km',
                        'rotate' => -45,
                    ],
                    'lineStyle' => [
                        'width' => 1,
                    ],
                    'symbolSize' => 6,
                    'showSymbol' => true,
                    'areaStyle' => [
                        'opacity' => 0.3,
                        'color' => 'rgba(227, 73, 2, 0.3)',
                    ],
                    'emphasis' => [
                        'focus' => 'series',
                    ],
                    'data' => $this->getData(),
                ],
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    private function getData(): array
    {
        /** @var SerializableDateTime $startDate */
        $startDate = SerializableDateTime::createFromFormat('Y-m-d', $this->now->modify('-10 weeks')->format('Y-m-d'));
        $interval = new \DateInterval('P1W');
        $period = new \DatePeriod($startDate, $interval, $this->now->modify('+1 day'));

        $distancePerWeek = [];

        foreach ($period as $date) {
            $distancePerWeek[$date->format('YW')] = [
                $date->modify('monday this week')->format('Y-m-d'),
                0,
            ];
        }

        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($this->activities as $activity) {
            $week = $activity->getStartDate()->format('YW');
            if (!array_key_exists($week, $distancePerWeek)) {
                continue;
            }
            $distancePerWeek[$week][1] += $activity->getDistance();
        }

        return array_values($distancePerWeek);
    }
}
