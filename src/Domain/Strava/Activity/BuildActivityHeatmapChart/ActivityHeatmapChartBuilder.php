<?php

namespace App\Domain\Strava\Activity\BuildActivityHeatmapChart;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class ActivityHeatmapChartBuilder
{
    private readonly SerializableDateTime $fromDate;
    private readonly SerializableDateTime $toDate;
    private bool $animation;
    private ?string $backgroundColor;

    private function __construct(
        private readonly ActivityCollection $activities,
        private readonly SerializableDateTime $now,
    ) {
        $this->animation = false;
        $this->backgroundColor = '#ffffff';

        /** @var SerializableDateTime $fromDate */
        $fromDate = SerializableDateTime::createFromFormat('Y-m-d', $this->now->modify('-11 months')->format('Y-m-01'));
        $this->fromDate = $fromDate;
        /** @var SerializableDateTime $toDate */
        $toDate = SerializableDateTime::createFromFormat('Y-m-d', $this->now->format('Y-m-t'));
        $this->toDate = $toDate;
    }

    public static function fromActivities(
        ActivityCollection $activities,
        SerializableDateTime $now,
    ): self {
        return new self(
            activities: $activities,
            now: $now,
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
            'backgroundColor' => $this->backgroundColor,
            'animation' => $this->animation,
            'title' => [
                'left' => 'center',
                'text' => sprintf('%s - %s', $this->fromDate->format('M Y'), $this->toDate->format('M Y')),
            ],
            'tooltip' => [
            ],
            'visualMap' => [
                'type' => 'piecewise',
                'left' => 'center',
                'bottom' => 0,
                'orient' => 'horizontal',
                'pieces' => [
                    [
                        'min' => 0,
                        'max' => 0,
                        'color' => '#cdd9e5',
                        'label' => 'No activities',
                    ],
                    [
                        'min' => 1,
                        'max' => 75,
                        'color' => '#68B34B',
                        'label' => 'Low',
                    ],
                    [
                        'min' => 76,
                        'max' => 125,
                        'color' => '#FAB735',
                        'label' => 'Medium',
                    ],
                    [
                        'min' => 126,
                        'max' => 200,
                        'color' => '#FF8E14',
                        'label' => 'High',
                    ],
                    [
                        'min' => 200,
                        'color' => '#FF0C0C',
                        'label' => 'Very high',
                    ],
                ],
            ],
            'calendar' => [
                'left' => 40,
                'cellSize' => [
                    'auto',
                    13,
                ],
                'range' => [$this->fromDate->format('Y-m-d'), $this->toDate->format('Y-m-d')],
                'itemStyle' => [
                    'borderWidth' => 3,
                    'opacity' => 0,
                ],
                'splitLine' => [
                    'show' => false,
                ],
                'yearLabel' => [
                    'show' => false,
                ],
                'dayLabel' => [
                    'firstDay' => 1,
                    'align' => 'right',
                    'fontSize' => 10,
                    'nameMap' => [
                        'Sun',
                        'Mon',
                        'Tue',
                        'Wed',
                        'Thu',
                        'Fri',
                        'Sat',
                    ],
                ],
            ],
            'series' => [
                'type' => 'heatmap',
                'coordinateSystem' => 'calendar',
                'data' => $this->getData(),
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    private function getData(): array
    {
        $activities = array_filter(
            $this->activities->toArray(),
            fn (Activity $activity) => $activity->getStartDate()->isAfterOrOn($this->fromDate) && $activity->getStartDate()->isBeforeOrOn($this->toDate)
        );

        $data = $rawData = [];
        foreach ($activities as $activity) {
            if (!$intensity = $activity->getIntensity()) {
                continue;
            }

            $day = $activity->getStartDate()->format('Y-m-d');
            if (!array_key_exists($day, $rawData)) {
                $rawData[$day] = 0;
            }

            $rawData[$day] += $intensity;
        }

        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod(
            $this->fromDate,
            $interval,
            $this->toDate,
        );

        foreach ($period as $dt) {
            $day = $dt->format('Y-m-d');
            if (!array_key_exists($day, $rawData)) {
                $data[] = [$day, 0];

                continue;
            }

            $data[] = [$day, $rawData[$day]];
        }

        return $data;
    }
}
