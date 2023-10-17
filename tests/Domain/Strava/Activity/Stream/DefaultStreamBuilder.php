<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Domain\Strava\Activity\Stream\DefaultStream;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class DefaultStreamBuilder
{
    private int $activityId;
    private StreamType $streamType;
    private SerializableDateTime $createdOn;
    private array $data;

    private function __construct()
    {
        $this->activityId = 1234;
        $this->streamType = StreamType::WATTS;
        $this->createdOn = SerializableDateTime::fromString('2023-10-10');
        $this->data = [];
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): ActivityStream
    {
        return DefaultStream::fromState(
            activityId: $this->activityId,
            streamType: $this->streamType,
            streamData: $this->data,
            createdOn: $this->createdOn,
        );
    }

    public function withActivityId(int $activityId): self
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function withStreamType(StreamType $streamType): self
    {
        $this->streamType = $streamType;

        return $this;
    }

    public function withCreatedOn(SerializableDateTime $createdOn): self
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    public function withData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
