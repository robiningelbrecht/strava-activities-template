<?php

namespace App\Tests\Domain\Strava\Activity\Stream\ImportActivityStreams;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\ImportActivityStreams\ImportActivityStreams;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\CommandBus;
use App\Tests\DatabaseTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use App\Tests\Domain\Strava\Activity\Stream\DefaultStreamBuilder;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\SpyOutput;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

class ImportActivityStreamsCommandHandlerTest extends DatabaseTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;
    private SpyStrava $strava;

    public function testHandle(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(3);

        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(4)
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(5)
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(6)
                ->build()
        );
        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(7)
                ->build()
        );
        $this->getContainer()->get(ActivityStreamRepository::class)->add(
            DefaultStreamBuilder::fromDefaults()
                ->withActivityId(4)
                ->build()
        );

        $this->commandBus->dispatch(new ImportActivityStreams($output));

        $this->assertMatchesTextSnapshot($output);

        /** @var \App\Tests\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        $this->assertMatchesJsonSnapshot($fileSystem->getWrites());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->strava = $this->getContainer()->get(Strava::class);
    }
}
