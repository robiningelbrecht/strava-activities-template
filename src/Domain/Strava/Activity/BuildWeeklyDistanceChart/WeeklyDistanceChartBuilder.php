<?php

namespace App\Domain\Strava\Activity\BuildWeeklyDistanceChart;

use App\Domain\Strava\Activity\ActivityCollection;
use App\Domain\Strava\Calendar\Week;
use App\Domain\Strava\Calendar\WeekCollection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class WeeklyDistanceChartBuilder
{
    private bool $animation;
    private ?string $backgroundColor;
    private bool $useDataZoom;

    private function __construct(
        private readonly ActivityCollection $activities,
        private readonly SerializableDateTime $now,
    ) {
        $this->animation = false;
        $this->backgroundColor = '#ffffff';
        $this->useDataZoom = false;
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

    public function withDataZoom(bool $flag): self
    {
        $this->useDataZoom = $flag;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $weeks = WeekCollection::create(
            startDate: $this->activities->getFirstActivityStartDate(),
            now: $this->now
        );
        $zoomValueSpan = 10;
        if (!$this->useDataZoom) {
            $weeks = $weeks->slice(($zoomValueSpan + 1) * -1);
        }
        $data = $this->getData($weeks);
        $xAxisLabels = [];
        /** @var Week $week */
        foreach ($weeks as $week) {
            if ($week == $weeks->getFirst() || in_array($week->getLabel(), $xAxisLabels)) {
                $xAxisLabels[] = '';
                continue;
            }
            $xAxisLabels[] = $week->getLabel();
        }

        return [
            'animation' => $this->animation,
            'backgroundColor' => $this->backgroundColor,
            'color' => [
                '#E34902',
            ],
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => $this->useDataZoom ? '50px' : '3%',
                'containLabel' => true,
            ],
            'dataZoom' => $this->useDataZoom ? [
                [
                    'type' => 'inside',
                    'startValue' => count($weeks),
                    'endValue' => count($weeks) - $zoomValueSpan,
                    'minValueSpan' => $zoomValueSpan,
                    'maxValueSpan' => $zoomValueSpan,
                    'brushSelect' => false,
                    'zoomLock' => true,
                ],
                [
                ],
            ] : [],
            'xAxis' => [
                [
                    'type' => 'category',
                    'boundaryGap' => false,
                    'axisTick' => [
                        'show' => false,
                    ],
                    'data' => $xAxisLabels,
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
                    'max' => 50 * ceil(max($data) / 50),
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
                    'data' => $data,
                ],
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    private function getData(WeekCollection $weeks): array
    {
        $distancePerWeek = [];

        /** @var \App\Domain\Strava\Calendar\Week $week */
        foreach ($weeks as $week) {
            $distancePerWeek[$week->getId()] = 0;
        }

        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($this->activities as $activity) {
            $week = $activity->getStartDate()->format(Week::WEEK_ID_FORMAT);
            if (!array_key_exists($week, $distancePerWeek)) {
                continue;
            }
            $distancePerWeek[$week] += $activity->getDistanceInKilometer();
        }

        $distancePerWeek = array_map('round', $distancePerWeek);

        return array_values($distancePerWeek);
    }
}
