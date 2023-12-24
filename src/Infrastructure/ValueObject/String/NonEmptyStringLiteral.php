<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\String;

abstract readonly class NonEmptyStringLiteral implements \JsonSerializable, \Stringable
{
    final private function __construct(
        private string $string,
    ) {
        $this->validate($string);
    }

    protected function validate(string $string): void
    {
        if (empty($string)) {
            throw new \InvalidArgumentException(get_called_class().' can not be empty');
        }
    }

    public static function fromString(string $string): static
    {
        return new static($string);
    }

    public static function fromOptionalString(string $string = null): ?static
    {
        if (!$string) {
            return null;
        }

        return new static($string);
    }

    public function __toString(): string
    {
        return $this->string;
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }
}
