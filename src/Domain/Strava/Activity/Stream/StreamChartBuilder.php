<?php

namespace App\Domain\Strava\Activity\Stream;

final class StreamChartBuilder
{
    private bool $animation;
    private ?string $backgroundColor;

    private function __construct(
        private ActivityStream $stream,
    ) {
        $this->animation = false;
        $this->backgroundColor = '#ffffff';
    }

    public static function fromStream(ActivityStream $stream): self
    {
        return new self($stream);
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

    public function build(): array
    {
        $data = $this->stream->getData();

        return [
            'backgroundColor' => $this->backgroundColor,
            'animation' => $this->animation,
            'grid' => [
                'left' => 0,
                'right' => 0,
                'bottom' => 0,
                'top' => 10,
                'containLabel' => true,
            ],
            'tooltip' => [
                'trigger' => 'axis',
                'formatter' => '<b>{c0}</b> '.$this->stream->getStreamType()->value,
            ],
            'xAxis' => [
                'show' => false,
                'type' => 'category',
            ],
            'yAxis' => [
                'type' => 'value',
                'interval' => ceil(max($data) / 10) * 10,
            ],
            'series' => [
                [
                    'data' => $this->stream->getData(),
                    'type' => 'line',
                    'smooth' => false,
                    'symbol' => 'none',
                ],
            ],
        ];
    }
}
