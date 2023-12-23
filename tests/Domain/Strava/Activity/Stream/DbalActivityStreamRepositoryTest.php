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
use App\Infrastructure\ValueObject\Time\Year;
use App\Tests\DatabaseTestCase;

class DbalActivityStreamRepositoryTest extends DatabaseTestCase
{
    private ActivityStreamRepository $activityStreamRepository;
    private ActivityStreamDetailsRepository $activityStreamDetailsRepository;

    public function testIsImportedForActivity(): void
    {
        $stream = DefaultStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertTrue($this->activityStreamDetailsRepository->isImportedForActivity($stream->getActivityId()));
        $this->assertFalse($this->activityStreamDetailsRepository->isImportedForActivity(ActivityId::fromUnprefixed('1')));
    }

    public function testHasOneForActivityAndStreamType(): void
    {
        $stream = DefaultStreamBuilder::fromDefaults()->build();
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
        $stream = DefaultStreamBuilder::fromDefaults()->build();
        $this->activityStreamRepository->add($stream);

        $this->assertEquals(
            ActivityStreamCollection::fromArray([$stream]),
            $this->activityStreamDetailsRepository->findByStreamType($stream->getStreamType())
        );
    }

    public function testFindByActivityAndStreamTypes(): void
    {
        $streamOne = DefaultStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = DefaultStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::CADENCE)
            ->build();
        $this->activityStreamRepository->add($streamTwo);
        $this->activityStreamRepository->add(
            DefaultStreamBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed(1))
                ->withStreamType(StreamType::HEART_RATE)
                ->build()
        );
        $this->activityStreamRepository->add(
            DefaultStreamBuilder::fromDefaults()
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
        $streamOne = DefaultStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = DefaultStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::CADENCE)
            ->build();
        $this->activityStreamRepository->add($streamTwo);
        $this->activityStreamRepository->add(
            DefaultStreamBuilder::fromDefaults()
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
        $streamOne = DefaultStreamBuilder::fromDefaults()
            ->withActivityId(ActivityId::fromUnprefixed(1))
            ->withStreamType(StreamType::WATTS)
            ->build();
        $this->activityStreamRepository->add($streamOne);
        $streamTwo = DefaultStreamBuilder::fromDefaults()
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
