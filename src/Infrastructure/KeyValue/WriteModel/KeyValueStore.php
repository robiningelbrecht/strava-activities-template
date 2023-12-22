<?php

declare(strict_types=1);

namespace App\Infrastructure\KeyValue\WriteModel;

use App\Infrastructure\KeyValue\KeyValue;

interface KeyValueStore
{
    public function save(KeyValue $keyValue): void;
}
