<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

use App\Domain\Strava\Activity\ActivityCollection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class DistanceOverTimePerGearChartBuilder
{
    private function __construct(
        private GearCollection $gears,
        private ActivityCollection $activities,
        private SerializableDateTime $now,
    ) {
    }

    public static function fromGearAndActivities(
        GearCollection $gearCollection,
        ActivityCollection $activityCollection,
        SerializableDateTime $now
    ): self {
        return new self(
            gears: $gearCollection,
            activities: $activityCollection,
            now: $now
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $startDate = $this->activities->getFirstActivityStartDate();
        $gears = $this->gears->sortByIsRetired();

        $interval = new \DateInterval('P1M');
        $period = new \DatePeriod(
            $startDate->modify('first day of this month'),
            $interval,
            $this->now->modify('last day of this month')
        );

        $xAxisValues = [];
        $distancePerGearAndMonth = [];
        foreach ($period as $date) {
            $xAxisValues[] = $date->format('F Y');
            /** @var \App\Domain\Strava\Gear\Gear $gear */
            foreach ($gears as $gear) {
                $distancePerGearAndMonth[$gear->getId()][$date->format('Ym')] = 0;
            }
        }
        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($this->activities as $activity) {
            if (!$activity->getGearId()) {
                continue;
            }
            $month = $activity->getStartDate()->format('Ym');
            $distancePerGearAndMonth[$activity->getGearId()][$month] += $activity->getDistance();
        }

        $series = [];
        $selectedSeries = [];

        foreach ($gears as $gear) {
            $selectedSeries[$gear->getName()] = !$gear->isRetired();
            $series[] = [
                'name' => $gear->getName(),
                'type' => 'bar',
                'barGap' => 0,
                'emphasis' => [
                    'focus' => 'series',
                ],
                'label' => [
                    'show' => true,
                    'position' => 'insideBottom',
                    'verticalAlign' => 'middle',
                    'align' => 'left',
                    'color' => '#000',
                    'rotate' => 90,
                    'distance' => 15,
                    'formatter' => '{distance|{c} km} - {a}',
                    'rich' => [
                        'distance' => [
                            'fontSize' => 14,
                            'fontWeight' => 'bold',
                        ],
                    ],
                ],
                'data' => array_values($distancePerGearAndMonth[$gear->getId()]),
            ];
        }

        return [
            'animation' => true,
            'color' => ['#91cc75', '#fac858', '#ee6666', '#73c0de', '#3ba272', '#fc8452', '#9a60b4', '#ea7ccc'],
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '50px',
                'containLabel' => true,
            ],
            'tooltip' => [
                'trigger' => 'axis',
                'axisPointer' => [
                    'type' => 'none',
                ],
            ],
            'legend' => [
                'selected' => $selectedSeries,
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
            'xAxis' => [
                [
                    'type' => 'category',
                    'axisTick' => [
                        'show' => false,
                    ],
                    'data' => $xAxisValues,
                ],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                ],
            ],
            'dataZoom' => [
                [
                    'type' => 'inside',
                    'startValue' => count($xAxisValues) - 4,
                    'endValue' => count($xAxisValues),
                    'minValueSpan' => 4,
                    'maxValueSpan' => 4,
                    'brushSelect' => false,
                    'zoomLock' => true,
                ],
                [
                ],
            ],
            'series' => $series,
        ];
    }
}
