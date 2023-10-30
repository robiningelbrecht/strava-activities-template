<?php

namespace App\Tests\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\Stream\ActivityStreamCollection;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\DbalActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypeCollection;
use App\Tests\DatabaseTestCase;

class DbalActivityStreamRepositoryTest extends DatabaseTestCase
{
    private ActivityStreamRepository $activityStreamRepository;

    public function testHasOneForActivity(): void
    {
        $stream = DefaultStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertTrue($this->activityStreamRepository->hasOneForActivity($stream->getActivityId()));
        $this->assertFalse($this->activityStreamRepository->hasOneForActivity('1'));
    }

    public function testHasOneForActivityAndStreamType(): void
    {
        $stream = DefaultStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertTrue($this->activityStreamRepository->hasOneForActivityAndStreamType(
            activityId: $stream->getActivityId(),
            streamType: $stream->getStreamType()
        ));
        $this->assertFalse($this->activityStreamRepository->hasOneForActivityAndStreamType(
            activityId: 1,
            streamType: $stream->getStreamType()
        ));
        $this->assertFalse($this->activityStreamRepository->hasOneForActivityAndStreamType(
            activityId: $stream->getActivityId(),
            streamType: StreamType::CADENCE
        ));
    }

    public function testFindByStreamType(): void
    {
        $stream = DefaultStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertEquals(
            ActivityStreamCollection::fromArray([$stream]),
            $this->activityStreamRepository->findByStreamType($stream->getStreamType())
        );
    }

    public function testFindByActivityAndStreamTypes(): void
    {
        $streamOne = DefaultStreamBuilder::fromDefaults()
            ->withActivityId(1)
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = DefaultStreamBuilder::fromDefaults()
            ->withActivityId(1)
            ->withStreamType(StreamType::CADENCE)
            ->build();
        $this->activityStreamRepository->add($streamTwo);
        $this->activityStreamRepository->add(
            DefaultStreamBuilder::fromDefaults()
                ->withActivityId(1)
                ->withStreamType(StreamType::HEART_RATE)
                ->build()
        );
        $this->activityStreamRepository->add(
            DefaultStreamBuilder::fromDefaults()
                ->withActivityId(2)
                ->withStreamType(StreamType::CADENCE)
                ->build()
        );

        $this->assertEquals(
            ActivityStreamCollection::fromArray([$streamTwo, $streamOne]),
            $this->activityStreamRepository->findByActivityAndStreamTypes(
                activityId: 1,
                streamTypes: StreamTypeCollection::fromArray([StreamType::WATTS, StreamType::CADENCE])
            )
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityStreamRepository = new DbalActivityStreamRepository(
            $this->getConnection()
        );
    }
}
