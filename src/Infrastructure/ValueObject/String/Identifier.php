<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\String;

abstract readonly class Identifier extends NonEmptyStringLiteral implements \JsonSerializable
{
    protected function validate(string $string): void
    {
        parent::validate($string);

        if (!self::startsWithPrefix($string)) {
            throw new \InvalidArgumentException('Identifier does not start with prefix "'.static::getPrefix().'", got: '.$string);
        }
    }

    private static function startsWithPrefix(string $identifier): bool
    {
        if ('' === static::getPrefix()) {
            return true;
        }

        return str_starts_with($identifier, static::getPrefix());
    }

    public static function fromUnprefixed(string $unprefixed): static
    {
        return static::fromString(static::getPrefix().$unprefixed);
    }

    public static function fromOptionalUnprefixed(?string $unprefixed): ?static
    {
        if (is_null($unprefixed)) {
            return null;
        }

        return static::fromUnprefixed($unprefixed);
    }

    public function toUnprefixedString(): string
    {
        return str_replace($this::getPrefix(), '', (string) $this);
    }

    abstract public static function getPrefix(): string;
}
