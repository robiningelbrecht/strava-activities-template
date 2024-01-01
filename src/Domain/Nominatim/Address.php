<?php

declare(strict_types=1);

namespace App\Domain\Nominatim;

final readonly class Address implements \JsonSerializable
{
    private function __construct(
        private array $data,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self($data);
    }

    public function getCountryCode(): string
    {
        return $this->data['country_code'];
    }

    public function getState(): string
    {
        return $this->data['state'];
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
