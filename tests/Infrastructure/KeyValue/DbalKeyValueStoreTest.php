<?php

namespace App\Tests\Infrastructure\KeyValue;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\ReadModel\DbalKeyValueStore as DbalKeyValueStoreRead;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\KeyValue\WriteModel\DbalKeyValueStore;
use App\Tests\DatabaseTestCase;

class DbalKeyValueStoreTest extends DatabaseTestCase
{
    private DbalKeyValueStore $keyValueStore;
    private DbalKeyValueStoreRead $keyValueStoreRead;

    public function testFind(): void
    {
        $keyValue = KeyValue::fromState(
            key: Key::ATHLETE_BIRTHDAY,
            value: Value::fromString('1989-08-14'),
        );
        $this->keyValueStore->save($keyValue);
        $this->keyValueStore->save($keyValue);

        $this->assertEquals(
            $keyValue,
            $this->keyValueStoreRead->find(Key::ATHLETE_BIRTHDAY)
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->keyValueStoreRead->find(Key::ATHLETE_BIRTHDAY);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyValueStore = new DbalKeyValueStore(
            $this->getConnectionFactory()
        );
        $this->keyValueStoreRead = new DbalKeyValueStoreRead(
            $this->getConnectionFactory()
        );
    }
}
