<?php

namespace App\Domain\Strava\Activity\BuildActivityHeatmapChart;

use App\Infrastructure\CQRS\CommandBus;
use App\Tests\DatabaseTestCase;
use App\Tests\ProvideTestData;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

class BuildActivityHeatmapChartCommandHandlerTest extends DatabaseTestCase
{
    use MatchesSnapshots;
    use ProvideTestData;

    private CommandBus $commandBus;
    private string $snapshotName;

    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildActivityHeatmapChart());

        /** @var \App\Tests\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        $this->assertMatchesJsonSnapshot($fileSystem->getWrites());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
