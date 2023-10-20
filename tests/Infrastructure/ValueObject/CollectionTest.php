<?php

namespace App\Tests\Infrastructure\ValueObject;

use App\Infrastructure\ValueObject\Weight;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testEmpty(): void
    {
        $this->assertEquals(
            ATestCollection::empty(),
            ATestCollection::fromArray([])
        );
        $this->assertTrue(ATestCollection::empty()->isEmpty());
    }

    public function testHas(): void
    {
        $collection = ATestCollection::empty()
            ->add(Weight::fromKilograms(10));

        $this->assertTrue(
            $collection->has(Weight::fromKilograms(10))
        );
        $this->assertFalse(
            $collection->has(Weight::fromKilograms(20))
        );
    }

    public function testMergeWith(): void
    {
        $collection = ATestCollection::empty()
            ->add(Weight::fromKilograms(10))
            ->mergeWith(ATestCollection::fromArray([Weight::fromKilograms(20)]));

        $this->assertEqualsCanonicalizing(
            $collection,
            ATestCollection::fromArray([Weight::fromKilograms(10), Weight::fromKilograms(20)])
        );

        $this->assertEqualsCanonicalizing(
            $collection,
            ATestCollection::fromArray([Weight::fromKilograms(20), Weight::fromKilograms(10)])
        );
    }

    public function testiItShouldGuardCollectionItemType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Item must be an instance of App\Infrastructure\ValueObject\Weight');

        ATestCollection::empty()->add('wrong');
    }
}
