<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\Activity;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class ActivityBuilder
{
    private int $activityId;
    private SerializableDateTime $startDateTime;
    private array $data;
    private ?string $gearId;

    private function __construct()
    {
        $this->activityId = 903645;
        $this->startDateTime = SerializableDateTime::fromString('2023-10-10');
        $this->data = [
            'kudos_count' => 1,
        ];
        $this->gearId = null;
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): Activity
    {
        return Activity::fromState(
            activityId: $this->activityId,
            startDateTime: $this->startDateTime,
            data: $this->data,
            gearId: $this->gearId
        );
    }

    public function withActivityId(int $activityId): self
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function withGearId(string $gearId): self
    {
        $this->gearId = $gearId;

        return $this;
    }

    public function withStartDateTime(SerializableDateTime $startDateTime): self
    {
        $this->startDateTime = $startDateTime;

        return $this;
    }
}