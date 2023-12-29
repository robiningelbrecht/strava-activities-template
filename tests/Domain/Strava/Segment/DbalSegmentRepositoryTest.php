<?php

namespace App\Tests\Domain\Strava\Segment;

use App\Domain\Strava\Segment\ReadModel\DbalSegmentDetailsRepository;
use App\Domain\Strava\Segment\ReadModel\SegmentDetailsRepository;
use App\Domain\Strava\Segment\SegmentCollection;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Strava\Segment\SegmentEffort\WriteModel\SegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentId;
use App\Domain\Strava\Segment\WriteModel\DbalSegmentRepository;
use App\Domain\Strava\Segment\WriteModel\SegmentRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\String\Name;
use App\Tests\DatabaseTestCase;
use App\Tests\Domain\Strava\Segment\SegmentEffort\SegmentEffortBuilder;

class DbalSegmentRepositoryTest extends DatabaseTestCase
{
    private SegmentRepository $segmentRepository;
    private SegmentDetailsRepository $segmentDetailsRepository;

    public function testFindAndSave(): void
    {
        $segment = SegmentBuilder::fromDefaults()
            ->build();
        $this->segmentRepository->add($segment);

        $this->assertEquals(
            $segment,
            $this->segmentDetailsRepository->find($segment->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->segmentDetailsRepository->find(SegmentId::fromUnprefixed('1'));
    }

    public function testFindAll(): void
    {
        $segmentOne = SegmentBuilder::fromDefaults()
            ->withId(SegmentId::fromUnprefixed(1))
            ->withName(Name::fromString('A name'))
            ->build();
        $this->segmentRepository->add($segmentOne);
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
            ->withId(SegmentEffortId::fromUnprefixed(1))
            ->withSegmentId($segmentOne->getId())
            ->build()
        );
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withId(SegmentEffortId::fromUnprefixed(2))
                ->withSegmentId($segmentOne->getId())
                ->build()
        );
        $segmentTwo = SegmentBuilder::fromDefaults()
            ->withId(SegmentId::fromUnprefixed(2))
            ->withName(Name::fromString('C name'))
            ->build();
        $this->segmentRepository->add($segmentTwo);
        $segmentThree = SegmentBuilder::fromDefaults()
            ->withId(SegmentId::fromUnprefixed(3))
            ->withName(Name::fromString('B name'))
            ->build();
        $this->segmentRepository->add($segmentThree);
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withId(SegmentEffortId::fromUnprefixed(3))
                ->withSegmentId($segmentThree->getId())
                ->build()
        );

        $this->assertEquals(
            SegmentCollection::fromArray([$segmentOne, $segmentThree, $segmentTwo]),
            $this->segmentDetailsRepository->findAll()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->segmentRepository = new DbalSegmentRepository(
            $this->getConnectionFactory()
        );
        $this->segmentDetailsRepository = new DbalSegmentDetailsRepository(
            $this->getConnectionFactory()
        );
    }
}
