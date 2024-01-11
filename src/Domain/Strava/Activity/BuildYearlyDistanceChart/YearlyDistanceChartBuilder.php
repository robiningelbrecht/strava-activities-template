<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BuildYearlyDistanceChart;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class YearlyDistanceChartBuilder
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
        $months = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'May',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Aug',
            '09' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec',
        ];

        $xAxisLabels = [];
        foreach ($months as $month) {
            $xAxisLabels = [...$xAxisLabels, ...array_fill(0, 31, $month)];
        }

        $series = [];
        /** @var \App\Infrastructure\ValueObject\Time\Year $year */
        foreach ($this->activities->getUniqueYears() as $year) {
            $series[(string) $year] = [
                'name' => (string) $year,
                'type' => 'line',
                'smooth' => true,
                'showSymbol' => false,
                'data' => [],
            ];

            $runningSum = 0;
            foreach ($months as $monthNumber => $label) {
                for ($i = 0; $i < 31; ++$i) {
                    $date = SerializableDateTime::fromString(sprintf(
                        '%s-%s-%s',
                        $year,
                        $monthNumber,
                        str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT))
                    );
                    $activitiesOnThisDay = $this->activities->filterOnDate($date);

                    if ($date->isAfter($this->now)) {
                        break 2;
                    }

                    $runningSum += $activitiesOnThisDay->sum(fn (Activity $activity) => $activity->getDistanceInKilometer());
                    $series[(string) $year]['data'][] = round($runningSum);
                }
            }
        }

        return [
            'animation' => $this->animation,
            'backgroundColor' => $this->backgroundColor,
            'grid' => [
                'left' => '40px',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'xAxis' => [
                [
                    'type' => 'category',
                    'axisTick' => [
                        'show' => false,
                    ],
                    'axisLabel' => [
                        'interval' => 31,
                    ],
                    'data' => $xAxisLabels,
                ],
            ],
            'legend' => [
                'show' => true,
            ],
            'tooltip' => [
                'show' => true,
                'trigger' => 'axis',
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'name' => 'Distance in km',
                    'nameRotate' => 90,
                    'nameLocation' => 'middle',
                    'nameGap' => 50,
                ],
            ],
            'series' => array_values($series),
        ];
    }
}
