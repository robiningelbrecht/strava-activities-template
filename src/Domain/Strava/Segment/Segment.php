<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment;

use App\Infrastructure\ValueObject\String\Name;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final readonly class Segment
{
    /**
     * @param array<mixed> $data
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private int $segmentId,
        #[ORM\Column(type: 'string', nullable: true)]
        private Name $name,
        #[ORM\Column(type: 'json')]
        private array $data,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function create(
        int $segmentId,
        Name $name,
        array $data,
    ): self {
        return new self(
            segmentId: $segmentId,
            name: $name,
            data: $data,
        );
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromState(
        int $segmentId,
        Name $name,
        array $data,
    ): self {
        return new self(
            segmentId: $segmentId,
            name: $name,
            data: $data,
        );
    }

    public function getId(): int
    {
        return $this->segmentId;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
