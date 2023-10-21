<?php

namespace App\Infrastructure\ValueObject;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class RandomUuidFactory implements UuidFactory
{
    public static function random(): UuidInterface
    {
        return Uuid::uuid4();
    }
}
