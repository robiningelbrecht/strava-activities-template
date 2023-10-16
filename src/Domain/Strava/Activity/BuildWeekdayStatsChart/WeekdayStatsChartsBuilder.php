<?php

namespace App\Domain\Strava\Activity\BuildWeekdayStatsChart;

use App\Domain\Strava\Activity\Activity;
use Carbon\CarbonInterval;

final class WeekdayStatsChartsBuilder
{
    private bool $animation;
    private ?string $backgroundColor;

    /**
     * @param Activity[] $activities
     */
    private function __construct(
        private readonly array $activities,
    ) {
        $this->animation = false;
        $this->backgroundColor = '#ffffff';
    }

    /**
     * @param Activity[] $activities
     */
    public static function fromActivities(
        array $activities,
    ): self {
        return new self($activities);
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
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'legend' => [
                'show' => false,
            ],
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{d}%',
            ],
            'series' => [
                [
                    'type' => 'pie',
                    'label' => [
                        'alignTo' => 'edge',
                        'minMargin' => 5,
                        'edgeDistance' => 10,
                        'formatter' => "{weekday|{@[1]}}\n{sub|{@[2]} rides, {@[3]} km, {@[4]}}",
                        'lineHeight' => 15,
                        'rich' => [
                            'weekday' => [
                                'fontWeight' => 'bold',
                            ],
                            'sub' => [
                                'fontSize' => 12,
                            ],
                        ],
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
        $statistics = [];
        $daysOfTheWeekMap = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $totalMovingTime = array_sum(array_map(fn (Activity $activity) => $activity->getMovingTime(), $this->activities));

        foreach ([1, 2, 3, 4, 5, 6, 0] as $weekDay) {
            $statistics[$weekDay] = [
                'numberOfRides' => 0,
                'totalDistance' => 0,
                'movingTime' => 0,
                'percentage' => 0,
            ];
        }

        foreach ($this->activities as $activity) {
            $weekDay = $activity->getStartDate()->format('w');

            ++$statistics[$weekDay]['numberOfRides'];
            $statistics[$weekDay]['totalDistance'] += $activity->getDistance();
            $statistics[$weekDay]['movingTime'] += $activity->getMovingTime();
            $statistics[$weekDay]['percentage'] = round($statistics[$weekDay]['movingTime'] / $totalMovingTime * 100);
        }

        $data = [];
        foreach ($statistics as $weekday => $statistic) {
            $movingTime = CarbonInterval::seconds($statistic['movingTime'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
            $data[] = [$statistic['percentage'], $daysOfTheWeekMap[$weekday], $statistic['numberOfRides'], $statistic['totalDistance'], $movingTime];
        }

        return $data;
    }
}
