<?php

namespace App\Tests\Domain\Strava\Segment\SegmentEffort;

use App\Domain\Strava\Segment\SegmentEffort\ReadModel\DbalSegmentEffortDetailsRepository;
use App\Domain\Strava\Segment\SegmentEffort\ReadModel\SegmentEffortDetailsRepository;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortCollection;
use App\Domain\Strava\Segment\SegmentEffort\WriteModel\DbalSegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentEffort\WriteModel\SegmentEffortRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\Year;
use App\Tests\DatabaseTestCase;

class DbalSegmentEffortRepositoryTest extends DatabaseTestCase
{
    private SegmentEffortRepository $segmentEffortRepository;
    private SegmentEffortDetailsRepository $segmentEffortDetailsRepository;

    public function testFindAndSave(): void
    {
        $segmentEffort = SegmentEffortBuilder::fromDefaults()
            ->build();
        $this->segmentEffortRepository->add($segmentEffort);

        $this->assertEquals(
            $segmentEffort,
            $this->segmentEffortDetailsRepository->find($segmentEffort->getId())
        );
    }

    public function testUpdate(): void
    {
        $segmentEffort = SegmentEffortBuilder::fromDefaults()
            ->build();
        $this->segmentEffortRepository->add($segmentEffort);
        $segmentEffort = SegmentEffortBuilder::fromDefaults()
            ->withData(['segment' => 'lol'])
            ->build();
        $this->segmentEffortRepository->update($segmentEffort);

        $this->assertEquals(
            SegmentEffortBuilder::fromDefaults()->build(),
            $this->segmentEffortDetailsRepository->find($segmentEffort->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->segmentEffortDetailsRepository->find(1);
    }

    public function testFindBySegmentId(): void
    {
        $segmentEffortOne = SegmentEffortBuilder::fromDefaults()
            ->withId(1)
            ->withSegmentId(1)
            ->build();
        $this->segmentEffortRepository->add($segmentEffortOne);

        $segmentEffortTwo = SegmentEffortBuilder::fromDefaults()
            ->withId(2)
            ->withSegmentId(1)
            ->build();
        $this->segmentEffortRepository->add($segmentEffortTwo);

        $segmentEffortThree = SegmentEffortBuilder::fromDefaults()
            ->withId(3)
            ->withSegmentId(2)
            ->build();
        $this->segmentEffortRepository->add($segmentEffortThree);

        $this->assertEquals(
            SegmentEffortCollection::fromArray([$segmentEffortOne, $segmentEffortTwo]),
            $this->segmentEffortDetailsRepository->findBySegmentId($segmentEffortOne->getSegmentId())
        );
    }

    public function testFindByActivityId(): void
    {
        $segmentEffortOne = SegmentEffortBuilder::fromDefaults()
            ->withId(1)
            ->withActivityId(1)
            ->build();
        $this->segmentEffortRepository->add($segmentEffortOne);

        $segmentEffortTwo = SegmentEffortBuilder::fromDefaults()
            ->withId(2)
            ->withActivityId(1)
            ->build();
        $this->segmentEffortRepository->add($segmentEffortTwo);

        $segmentEffortThree = SegmentEffortBuilder::fromDefaults()
            ->withId(3)
            ->withActivityId(2)
            ->build();
        $this->segmentEffortRepository->add($segmentEffortThree);

        $this->assertEquals(
            SegmentEffortCollection::fromArray([$segmentEffortOne, $segmentEffortTwo]),
            $this->segmentEffortDetailsRepository->findByActivityId($segmentEffortOne->getActivityId())
        );
    }

    public function testDelete(): void
    {
        $segmentEffortOne = SegmentEffortBuilder::fromDefaults()->build();
        $this->segmentEffortRepository->add($segmentEffortOne);

        $this->assertEquals(
            1,
            $this->getConnectionFactory()
                ->getForYear(Year::fromDate($segmentEffortOne->getStartDateTime()))
                ->executeQuery('SELECT COUNT(*) FROM SegmentEffort')->fetchOne()
        );

        $this->segmentEffortRepository->delete($segmentEffortOne);
        $this->assertEquals(
            0,
            $this->getConnectionFactory()
                ->getForYear(Year::fromDate($segmentEffortOne->getStartDateTime()))
                ->executeQuery('SELECT COUNT(*) FROM SegmentEffort')->fetchOne()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->segmentEffortRepository = new DbalSegmentEffortRepository(
            $this->getConnectionFactory()
        );
        $this->segmentEffortDetailsRepository = new DbalSegmentEffortDetailsRepository(
            $this->getConnectionFactory()
        );
    }
}
