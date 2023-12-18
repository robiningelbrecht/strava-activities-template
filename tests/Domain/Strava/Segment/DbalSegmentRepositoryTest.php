<?php

namespace App\Tests\Domain\Strava\Segment;

use App\Domain\Strava\Segment\DbalSegmentRepository;
use App\Domain\Strava\Segment\SegmentCollection;
use App\Domain\Strava\Segment\SegmentRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\String\Name;
use App\Tests\DatabaseTestCase;

class DbalSegmentRepositoryTest extends DatabaseTestCase
{
    private SegmentRepository $segmentRepository;

    public function testFindAndSave(): void
    {
        $segment = SegmentBuilder::fromDefaults()
            ->build();
        $this->segmentRepository->add($segment);

        $this->assertEquals(
            $segment,
            $this->segmentRepository->find($segment->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->segmentRepository->find(1);
    }

    public function testFindAll(): void
    {
        $segmentOne = SegmentBuilder::fromDefaults()
            ->withId(1)
            ->withName(Name::fromString('A name'))
            ->build();
        $this->segmentRepository->add($segmentOne);
        $segmentTwo = SegmentBuilder::fromDefaults()
            ->withId(2)
            ->withName(Name::fromString('C name'))
            ->build();
        $this->segmentRepository->add($segmentTwo);
        $segmentThree = SegmentBuilder::fromDefaults()
            ->withId(3)
            ->withName(Name::fromString('B name'))
            ->build();
        $this->segmentRepository->add($segmentThree);

        $this->assertEquals(
            SegmentCollection::fromArray([$segmentOne, $segmentThree, $segmentTwo]),
            $this->segmentRepository->findAll()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->segmentRepository = new DbalSegmentRepository(
            $this->getConnection()
        );
    }
}