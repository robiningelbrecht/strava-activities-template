<?php

namespace App\Tests\Domain\Strava\Activity\ImportActivities;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ImportActivities\ImportActivities;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\CommandBus;
use App\Tests\DatabaseTestCase;
use App\Tests\Domain\Strava\Activity\ActivityBuilder;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\SpyOutput;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

class ImportActivitiesCommandHandlerTest extends DatabaseTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;
    private SpyStrava $strava;

    public function testHandle(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(8);

        $this->getContainer()->get(ActivityRepository::class)->add(
            ActivityBuilder::fromDefaults()
                ->withActivityId(4)
                ->build()
        );

        $this->commandBus->dispatch(new ImportActivities($output));

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
