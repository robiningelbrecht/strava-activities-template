<?php

declare(strict_types=1);

namespace App\Infrastructure\KeyValue\WriteModel;

use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\KeyValue\KeyValue;

final readonly class DbalKeyValueStore implements KeyValueStore
{
    public function __construct(
        private ConnectionFactory $connectionFactory,
    ) {
    }

    public function save(KeyValue $keyValue): void
    {
        $sql = 'REPLACE INTO KeyValue (`key`, `value`)
        VALUES (:key, :value)';

        $this->connectionFactory->getDefault()->executeStatement($sql, [
            'key' => $keyValue->getKey()->value,
            'value' => $keyValue->getValue(),
        ]);
    }
}
