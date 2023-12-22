<?php

declare(strict_types=1);

namespace App\Infrastructure\KeyValue\ReadModel;

use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;

interface KeyValueStore
{
    public function find(Key $key): KeyValue;
}
