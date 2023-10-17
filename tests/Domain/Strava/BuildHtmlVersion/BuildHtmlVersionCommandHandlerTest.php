<?php

namespace App\Tests\Domain\Strava\BuildHtmlVersion;

use App\Domain\Strava\BuildHtmlVersion\BuildHtmlVersion;
use App\Infrastructure\CQRS\CommandBus;
use App\Tests\DatabaseTestCase;
use App\Tests\ProvideTestData;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

class BuildHtmlVersionCommandHandlerTest extends DatabaseTestCase
{
    use MatchesSnapshots;
    use ProvideTestData;

    private CommandBus $commandBus;
    private string $snapshotName;

    public function testHandle(): void
    {
        $this->provideFullTestSet();

        $this->commandBus->dispatch(new BuildHtmlVersion());

        /** @var \App\Tests\SpyFileSystem $fileSystem */
        $fileSystem = $this->getContainer()->get(FilesystemOperator::class);
        foreach ($fileSystem->getWrites() as $location => $content) {
            $this->snapshotName = $location;
            $this->assertMatchesHtmlSnapshot($content);
        }
    }

    protected function getSnapshotId(): string
    {
        return (new \ReflectionClass($this))->getShortName().'--'.
            $this->name().'--'.
            $this->snapshotName;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
