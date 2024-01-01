<?php

declare(strict_types=1);

namespace App\Domain\Nominatim;

final readonly class Address implements \JsonSerializable
{
    /**
     * @param array<mixed> $data
     */
    private function __construct(
        private array $data,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromState(array $data): self
    {
        return new self($data);
    }

    public function getCountryCode(): string
    {
        return $this->data['country_code'];
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
