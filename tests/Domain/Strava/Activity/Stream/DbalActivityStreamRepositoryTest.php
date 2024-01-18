<?php

namespace App\Tests\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\Stream\ActivityStreamCollection;
use App\Domain\Strava\Activity\Stream\ReadModel\ActivityStreamDetailsRepository;
use App\Domain\Strava\Activity\Stream\ReadModel\DbalActivityStreamDetailsRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypeCollection;
use App\Domain\Strava\Activity\Stream\WriteModel\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\WriteModel\DbalActivityStreamRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\Year;
use App\Tests\DatabaseTestCase;

class DbalActivityStreamRepositoryTest extends DatabaseTestCase
{
    private ActivityStreamRepository $activityStreamRepository;
    private ActivityStreamDetailsRepository $activityStreamDetailsRepository;

    public function testIsImportedForActivity(): void
    {
        $stream = ActivityStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertTrue($this->activityStreamDetailsRepository->isImportedForActivity($stream->getActivityId()));
        $this->assertFalse($this->activityStreamDetailsRepository->isImportedForActivity(ActivityId::fromUnprefixed('1')));
    }

    public function testUpdate(): void
    {
        $stream = ActivityStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertEmpty($stream->getBestAverages());

        $stream->updateBestAverages([1 => 1]);
        $this->activityStreamRepository->update($stream);

        $streams = $this->activityStreamDetailsRepository->findByActivityId($stream->getActivityId());
        /** @var \App\Domain\Strava\Activity\Stream\ActivityStream $stream */
        $stream = $streams->getFirst();

        $this->assertEquals([1 => 1], $stream->getBestAverages());
    }

    public function testHasOneForActivityAndStreamType(): void
    {
        $stream = ActivityStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertTrue($this->activityStreamDetailsRepository->hasOneForActivityAndStreamType(
            activityId: $stream->getActivityId(),
            streamType: $stream->getStreamType()
        ));
        $this->assertFalse($this->activityStreamDetailsRepository->hasOneForActivityAndStreamType(
            activityId: ActivityId::fromUnprefixed(1),
            streamType: $stream->getStreamType()
        ));
        $this->assertFalse($this->activityStreamDetailsRepository->hasOneForActivityAndStreamType(
            activityId: $stream->getActivityId(),
            streamType: StreamType::CADENCE
        ));
    }

    public function testFindByStreamType(): void
    {
        $stream = ActivityStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertEquals(
            ActivityStreamCollection::fromArray([$stream]),
            $this->activityStreamDetailsRepository->findByStreamType($stream->getStreamType())
        );
    }

    public function testFindByActivityAndStreamTypes(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::CADENCE)
            ->build();
        $this->activityStreamRepository->add($streamTwo);
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withStreamType(StreamType::HEART_RATE)
                ->build()
        );
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withStreamType(StreamType::CADENCE)
                ->build()
        );

        $this->assertEquals(
            ActivityStreamCollection::fromArray([$streamTwo, $streamOne]),
            $this->activityStreamDetailsRepository->findByActivityAndStreamTypes(
                activityId: ActivityId::fromUnprefixed(1),
                streamTypes: StreamTypeCollection::fromArray([StreamType::WATTS, StreamType::CADENCE])
            )
        );
    }

    public function testFindByActivity(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::CADENCE)
            ->build();
        $this->activityStreamRepository->add($streamTwo);
        $this->activityStreamRepository->add(
            ActivityStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(2))
                ->withStreamType(StreamType::CADENCE)
                ->build()
        );

        $this->assertEquals(
            ActivityStreamCollection::fromArray([$streamTwo, $streamOne]),
            $this->activityStreamDetailsRepository->findByActivityId(
                activityId: ActivityId::fromUnprefixed(1),
            )
        );
    }

    public function testDelete(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::CADENCE)
            ->build();
        $this->activityStreamRepository->add($streamTwo);

        $this->assertEquals(
            2,
            $this->getConnectionFactory()
                ->getForYear(Year::fromDate($streamOne->getCreatedOn()))
                ->executeQuery('SELECT COUNT(*) FROM ActivityStream')->fetchOne()
        );

        $this->activityStreamRepository->delete($streamOne);
        $this->assertEquals(
            1,
            $this->getConnectionFactory()
                ->getForYear(Year::fromDate($streamTwo->getCreatedOn()))
                ->executeQuery('SELECT COUNT(*) FROM ActivityStream')->fetchOne()
        );
    }

    public function testFindWithoutBestAverages(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::CADENCE)
            ->withBestAverages(['lol'])
            ->build();
        $this->activityStreamRepository->add($streamTwo);

        $this->assertEquals(
            ActivityStreamCollection::fromArray([$streamOne]),
            $this->activityStreamDetailsRepository->findWithoutBestAverages()
        );
    }

    public function testFindWithBestAverageFor(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->withBestAverages(['10' => 40])
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStreamType(StreamType::WATTS)
            ->withBestAverages(['10' => 30])
            ->build();
        $this->activityStreamRepository->add($streamTwo);

        $this->assertEquals(
            $streamOne,
            $this->activityStreamDetailsRepository->findWithBestAverageFor(10, StreamType::WATTS)
        );
    }

    public function testFindWithBestAverageForItShouldThrowWhenNotFound(): void
    {
        $streamOne = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->withBestAverages(['10' => 40])
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = ActivityStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(2))
            ->withStreamType(StreamType::WATTS)
            ->withBestAverages(['10' => 30])
            ->build();
        $this->activityStreamRepository->add($streamTwo);

        $this->expectExceptionObject(new EntityNotFound('ActivityStream for average not found'));

        $this->activityStreamDetailsRepository->findWithBestAverageFor(20, StreamType::WATTS);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityStreamRepository = new DbalActivityStreamRepository(
            $this->getConnectionFactory(),
        );
        $this->activityStreamDetailsRepository = new DbalActivityStreamDetailsRepository(
            $this->getConnectionFactory(),
        );
    }
}
