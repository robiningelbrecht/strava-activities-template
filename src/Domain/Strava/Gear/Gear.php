<?php

namespace App\Domain\Strava\Gear;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Gear
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly string $gearId,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $createdOn,
        #[ORM\Column(type: 'integer')]
        private int $distanceInMeter,
        #[ORM\Column(type: 'json')]
        private array $data
    ) {
    }

    public static function create(
        string $gearId,
        array $data,
        int $distanceInMeter,
        SerializableDateTime $createdOn
    ): self {
        return new self(
            gearId: $gearId,
            createdOn: $createdOn,
            distanceInMeter: $distanceInMeter,
            data: $data
        );
    }

    public static function fromState(
        string $gearId,
        array $data,
        int $distanceInMeter,
        SerializableDateTime $createdOn
    ): self {
        return new self(
            gearId: $gearId,
            createdOn: $createdOn,
            distanceInMeter: $distanceInMeter,
            data: $data
        );
    }

    public function getId(): string
    {
        return $this->gearId;
    }

    public function getName(): string
    {
        return $this->data['name'];
    }

    public function getDistanceInMeter(): int
    {
        return $this->distanceInMeter;
    }

    public function getDistanceInKm(): float
    {
        return round($this->distanceInMeter / 1000);
    }

    public function isRetired(): bool
    {
        return $this->data['retired'] ?? false;
    }

    public function updateDistance(float $distance, float $convertedDistance): void
    {
        $this->distanceInMeter = $distance;
        $this->data['converted_distance'] = $convertedDistance;
    }

    public function getCreatedOn(): SerializableDateTime
    {
        return $this->createdOn;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
