<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject;

use Ramsey\Uuid\UuidInterface;

interface UuidFactory
{
    public static function random(): UuidInterface;
}
