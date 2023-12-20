<?php

namespace App\Tests\Domain\Strava\Segment\ImportSegments;

use App\Domain\Strava\Activity\WriteModel\ActivityRepository;
use App\Domain\Strava\Segment\ImportSegments\ImportSegments;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortRepository;
use App\Infrastructure\CQRS\CommandBus;
use App\Tests\DatabaseTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use App\Tests\Domain\Strava\Segment\SegmentEffort\SegmentEffortBuilder;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class ImportSegmentsCommandHandlerTest extends DatabaseTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $output = new SpyOutput();

        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(1)
                ->withData([
                    'segment_efforts' => [
                        [
                            'id' => 1,
                            'start_date_local' => '2023-07-29T09:34:03Z',
                            'segment' => [
                                'id' => 1,
                                'name' => 'Segment One',
                            ],
                        ],
                    ],
                ])
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(2)
                ->withData([
                    'segment_efforts' => [
                        [
                            'id' => 2,
                            'start_date_local' => '2023-07-29T09:34:03Z',
                            'segment' => [
                                'id' => 1,
                                'name' => 'Segment One',
                            ],
                        ],
                    ],
                ])
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(3)
                ->withData([])
                ->build()
        );
        $this->getContainer()->get(SegmentEffortRepository::class)->add(
            SegmentEffortBuilder::fromDefaults()
                ->withId(2)
                ->withSegmentId(1)
                ->withActivityId(9542782314)
                ->withData([
                    'elapsed_time' => 9.3,
                    'average_watts' => 200,
                    'distance' => 100,
                ])
                ->build()
        );

        $this->commandBus->dispatch(new ImportSegments($output));
        $this->assertMatchesTextSnapshot($output);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
