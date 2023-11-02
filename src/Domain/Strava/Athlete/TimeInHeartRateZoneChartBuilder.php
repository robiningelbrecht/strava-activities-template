<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete;

final readonly class TimeInHeartRateZoneChartBuilder
{
    private function __construct(
        private int $timeInSecondsInHeartRateZoneOne,
        private int $timeInSecondsInHeartRateZoneTwo,
        private int $timeInSecondsInHeartRateZoneThree,
        private int $timeInSecondsInHeartRateZoneFour,
        private int $timeInSecondsInHeartRateZoneFive,
    ) {
    }

    public static function fromTimeInZones(
        int $timeInSecondsInHeartRateZoneOne,
        int $timeInSecondsInHeartRateZoneTwo,
        int $timeInSecondsInHeartRateZoneThree,
        int $timeInSecondsInHeartRateZoneFour,
        int $timeInSecondsInHeartRateZoneFive,
    ): self {
        return new self(
            timeInSecondsInHeartRateZoneOne: $timeInSecondsInHeartRateZoneOne,
            timeInSecondsInHeartRateZoneTwo: $timeInSecondsInHeartRateZoneTwo,
            timeInSecondsInHeartRateZoneThree: $timeInSecondsInHeartRateZoneThree,
            timeInSecondsInHeartRateZoneFour: $timeInSecondsInHeartRateZoneFour,
            timeInSecondsInHeartRateZoneFive: $timeInSecondsInHeartRateZoneFive,
        );
    }

    /**
     * @return array<mixed>
     */
    public function build(): array
    {
        return [
            'backgroundColor' => null,
            'animation' => true,
            'grid' => [
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true,
            ],
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{d}%',
            ],
            'series' => [
                [
                    'type' => 'pie',
                    'itemStyle' => [
                        'borderColor' => '#fff',
                        'borderWidth' => 2,
                    ],
                    'label' => [
                        'formatter' => "{zone|{b}}\n{sub|{d}%}",
                        'lineHeight' => 15,
                        'rich' => [
                            'zone' => [
                                'fontWeight' => 'bold',
                            ],
                            'sub' => [
                                'fontSize' => 12,
                            ],
                        ],
                    ],
                    'data' => [
                        [
                            'value' => $this->timeInSecondsInHeartRateZoneOne,
                            'name' => 'Zone 1 (recovery)',
                            'itemStyle' => [
                                'color' => '#DF584A',
                            ],
                        ],
                        [
                            'value' => $this->timeInSecondsInHeartRateZoneTwo,
                            'name' => 'Zone 2 (aerobic)',
                            'itemStyle' => [
                                'color' => '#D63522',
                            ],
                        ],
                        [
                            'value' => $this->timeInSecondsInHeartRateZoneThree,
                            'name' => 'Zone 3 (aerobic/anaerobic)',
                            'itemStyle' => [
                                'color' => '#BD2D22',
                            ],
                        ],
                        [
                            'value' => $this->timeInSecondsInHeartRateZoneFour,
                            'name' => 'Zone 4 (anaerobic)',
                            'itemStyle' => [
                                'color' => '#942319',
                            ],
                        ],
                        [
                            'value' => $this->timeInSecondsInHeartRateZoneFive,
                            'name' => 'Zone 5 (maximal)',
                            'itemStyle' => [
                                'color' => '#6A1009',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
