<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\ValueObject;

use App\Infrastructure\ValueObject\UuidFactory;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class FakeUuidFactory implements UuidFactory
{
    public static function random(): UuidInterface
    {
        return Uuid::fromString('0025176c-5652-11ee-923d-02424dd627d5');
    }
}
