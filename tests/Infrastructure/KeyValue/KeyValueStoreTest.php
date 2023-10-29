<?php

namespace App\Tests\Infrastructure\KeyValue;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Tests\DatabaseTestCase;

class KeyValueStoreTest extends DatabaseTestCase
{
    private KeyValueStore $keyValueStore;

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
            $this->keyValueStore->find(Key::ATHLETE_BIRTHDAY)
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->keyValueStore->find(Key::ATHLETE_BIRTHDAY);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyValueStore = new KeyValueStore(
            $this->getConnection()
        );
    }
}
