<?php

declare(strict_types=1);

namespace App\Infrastructure\KeyValue\ReadModel;

use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\Value;

final readonly class DbalKeyValueStore implements KeyValueStore
{
    public function __construct(
        private ConnectionFactory $connectionFactory,
    ) {
    }

    public function find(Key $key): KeyValue
    {
        $queryBuilder = $this->connectionFactory->getReadOnly()->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('KeyValue')
            ->andWhere('`key` = :key')
            ->setParameter('key', $key->value);

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('KeyValue "%s" not found', $key->value));
        }

        return KeyValue::fromState(
            key: Key::from($result['key']),
            value: Value::fromString($result['value']),
        );
    }
}
