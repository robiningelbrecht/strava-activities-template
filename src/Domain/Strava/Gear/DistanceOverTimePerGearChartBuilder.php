<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear;

use App\Domain\Strava\Activity\ActivityCollection;
use App\Domain\Strava\Calendar\Month;
use App\Domain\Strava\Calendar\MonthCollection;

final readonly class DistanceOverTimePerGearChartBuilder
{
    private function __construct(
        private GearCollection $gears,
        private ActivityCollection $activities,
        private MonthCollection $months,
    ) {
    }

    public static function fromGearAndActivities(
        GearCollection $gearCollection,
        ActivityCollection $activityCollection,
        MonthCollection $months
    ): self {
        return new self(
            gears: $gearCollection,
            activities: $activityCollection,
            months: $months
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        $gears = $this->gears->sortByIsRetired();

        $xAxisValues = [];
        $distancePerGearAndMonth = [];
        /** @var \App\Domain\Strava\Calendar\Month $month */
        foreach ($this->months as $month) {
            $xAxisValues[] = $month->getLabel();
            /** @var \App\Domain\Strava\Gear\Gear $gear */
            foreach ($gears as $gear) {
                $distancePerGearAndMonth[$gear->getId()][$month->getId()] = 0;
            }
        }
        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($this->activities as $activity) {
            if (!$activity->getGearId()) {
                continue;
            }
            $month = $activity->getStartDate()->format(Month::MONTH_ID_FORMAT);
            $distancePerGearAndMonth[$activity->getGearId()][$month] += $activity->getDistanceInKilometer();
        }

        foreach ($distancePerGearAndMonth as $gearId => $months) {
            $distancePerGearAndMonth[$gearId] = array_map('round', $months);
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
