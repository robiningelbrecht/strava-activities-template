<?php

declare(strict_types=1);

namespace App\Infrastructure\KeyValue;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final readonly class KeyValue
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private Key $key,
        #[ORM\Column(type: 'text')]
        private Value $value,
    ) {
    }

    public static function fromState(
        Key $key,
        Value $value,
    ): self {
        return new self(
            key: $key,
            value: $value
        );
    }

    public function getKey(): Key
    {
        return $this->key;
    }

    public function getValue(): Value
    {
        return $this->value;
    }
}
