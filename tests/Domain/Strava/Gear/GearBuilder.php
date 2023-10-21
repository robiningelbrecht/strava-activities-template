<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Gear;

use App\Domain\Strava\Gear\Gear;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class GearBuilder
{
    private string $gearId;
    private SerializableDateTime $createdOn;
    private int $distanceInMeter;
    private array $data;

    private function __construct()
    {
        $this->gearId = '1';
        $this->createdOn = SerializableDateTime::fromString('2023-10-10');
        $this->distanceInMeter = 10023;
        $this->data = [
            'converted_distance' => 100.23,
            'name' => 'Existing gear',
        ];
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): Gear
    {
        return Gear::fromState(
            gearId: $this->gearId,
            data: $this->data,
            distanceInMeter: $this->distanceInMeter,
            createdOn: $this->createdOn,
        );
    }

    public function withGearId(string $gearId): self
    {
        $this->gearId = $gearId;

        return $this;
    }

    public function withDistanceInMeter(int $distanceInMeter): self
    {
        $this->distanceInMeter = $distanceInMeter;

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
