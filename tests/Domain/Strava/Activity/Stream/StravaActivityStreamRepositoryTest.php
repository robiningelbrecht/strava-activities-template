<?php

namespace App\Tests\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\Stream\ActivityStreamCollection;
use App\Domain\Strava\Activity\Stream\StravaActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypeCollection;
use App\Tests\DatabaseTestCase;

class StravaActivityStreamRepositoryTest extends DatabaseTestCase
{
    private StravaActivityStreamRepository $stravaActivityStreamRepository;

    public function testHasOneForActivity(): void
    {
        $stream = DefaultStreamBuilder::fromDefaults()->build();
        $this->stravaActivityStreamRepository->add($stream);

        $this->assertTrue($this->stravaActivityStreamRepository->hasOneForActivity($stream->getActivityId()));
        $this->assertFalse($this->stravaActivityStreamRepository->hasOneForActivity('1'));
    }

    public function testHasOneForActivityAndStreamType(): void
    {
        $stream = DefaultStreamBuilder::fromDefaults()->build();
        $this->stravaActivityStreamRepository->add($stream);

        $this->assertTrue($this->stravaActivityStreamRepository->hasOneForActivityAndStreamType(
            activityId: $stream->getActivityId(),
            streamType: $stream->getStreamType()
        ));
        $this->assertFalse($this->stravaActivityStreamRepository->hasOneForActivityAndStreamType(
            activityId: 1,
            streamType: $stream->getStreamType()
        ));
        $this->assertFalse($this->stravaActivityStreamRepository->hasOneForActivityAndStreamType(
            activityId: $stream->getActivityId(),
            streamType: StreamType::CADENCE
        ));
    }

    public function testFindByStreamType(): void
    {
        $stream = DefaultStreamBuilder::fromDefaults()->build();
        $this->stravaActivityStreamRepository->add($stream);

        $this->assertEquals(
            ActivityStreamCollection::fromArray([$stream]),
            $this->stravaActivityStreamRepository->findByStreamType($stream->getStreamType())
        );
    }

    public function testFindByActivityAndStreamTypes(): void
    {
        $streamOne = DefaultStreamBuilder::fromDefaults()
            ->withActivityId(1)
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->stravaActivityStreamRepository->add($streamOne);
        $streamTwo = DefaultStreamBuilder::fromDefaults()
            ->withActivityId(1)
            ->withStreamType(StreamType::CADENCE)
            ->build();
        $this->stravaActivityStreamRepository->add($streamTwo);
        $this->stravaActivityStreamRepository->add(
            DefaultStreamBuilder::fromDefaults()
                ->withActivityId(1)
                ->withStreamType(StreamType::HEART_RATE)
                ->build()
        );
        $this->stravaActivityStreamRepository->add(
            DefaultStreamBuilder::fromDefaults()
                ->withActivityId(2)
                ->withStreamType(StreamType::CADENCE)
                ->build()
        );

        $this->assertEquals(
            ActivityStreamCollection::fromArray([$streamTwo, $streamOne]),
            $this->stravaActivityStreamRepository->findByActivityAndStreamTypes(
                activityId: 1,
                streamTypes: StreamTypeCollection::fromArray([StreamType::WATTS, StreamType::CADENCE])
            )
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->stravaActivityStreamRepository = new StravaActivityStreamRepository(
            $this->getConnection()
        );
    }
}
