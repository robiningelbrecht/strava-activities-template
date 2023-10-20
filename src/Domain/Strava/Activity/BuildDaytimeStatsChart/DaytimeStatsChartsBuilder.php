<?php

namespace App\Domain\Strava\Activity\BuildDaytimeStatsChart;

final class DaytimeStatsChartsBuilder
{
    private bool $animation;
    private ?string $backgroundColor;

    private function __construct(
        private readonly DaytimeStats $daytimeStats,
    ) {
        $this->animation = false;
        $this->backgroundColor = '#ffffff';
    }

    public static function fromDaytimeStats(
        DaytimeStats $daytimeStats,
    ): self {
        return new self($daytimeStats);
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
                    'itemStyle' => [
                        'borderColor' => '#fff',
                        'borderWidth' => 2,
                    ],
                    'label' => [
                        'formatter' => "{daytime|{b}}\n{sub|{d}%}",
                        'lineHeight' => 15,
                        'rich' => [
                            'daytime' => [
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
        $data = [];
        foreach ($this->daytimeStats->getData() as $statistic) {
            $data[] = [
                'value' => $statistic['percentage'],
                'name' => $statistic['daytime']->getEmoji().' '.$statistic['daytime']->value,
            ];
        }

        return $data;
    }
}
