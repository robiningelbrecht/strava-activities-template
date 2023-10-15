<?php

namespace App\Domain\Strava\Gear;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

class Gear implements \JsonSerializable
{
    private function __construct(
        private array $data
    ) {
    }

    public static function create(array $data, SerializableDateTime $createdOn): self
    {
        $data['createdOn'] = $createdOn->getTimestamp();

        return new self($data);
    }

    public static function fromMap(array $data): self
    {
        return new self($data);
    }

    public function getId(): string
    {
        return $this->data['id'];
    }

    public function getName(): string
    {
        return $this->data['name'];
    }

    public function getDistance(): float
    {
        return round($this->data['distance'] / 1000);
    }

    public function isRetired(): bool
    {
        return $this->data['retired'] ?? false;
    }

    public function updateDistance(float $distance, float $convertedDistance): void
    {
        $this->data['distance'] = $distance;
        $this->data['converted_distance'] = $convertedDistance;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
