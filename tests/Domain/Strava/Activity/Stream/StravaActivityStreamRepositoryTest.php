<?php

namespace App\Tests\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\Stream\StravaActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
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

    public function testFindByStreamType(): void
    {
        $stream = DefaultStreamBuilder::fromDefaults()->build();
        $this->stravaActivityStreamRepository->add($stream);

        $this->assertEquals(
            [$stream],
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
            [$streamTwo, $streamOne],
            $this->stravaActivityStreamRepository->findByActivityAndStreamTypes(1, [StreamType::WATTS, StreamType::CADENCE])
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
