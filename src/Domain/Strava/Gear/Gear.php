<?php

namespace App\Domain\Strava\Gear;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Gear implements \JsonSerializable
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly string $gearId,
        #[ORM\Column(type: 'json')]
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
