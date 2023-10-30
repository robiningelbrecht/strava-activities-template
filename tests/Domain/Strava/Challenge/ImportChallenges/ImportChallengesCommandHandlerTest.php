<?php

namespace App\Tests\Domain\Strava\Challenge\ImportChallenges;

use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\Challenge\ImportChallenges\ImportChallenges;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\CommandBus;
use App\Tests\DatabaseTestCase;
use App\Tests\Domain\Strava\Challenge\ChallengeBuilder;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\SpyOutput;
use League\Flysystem\FilesystemOperator;
use Spatie\Snapshots\MatchesSnapshots;

class ImportChallengesCommandHandlerTest extends DatabaseTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;
    private SpyStrava $strava;

    public function testHandle(): void
    {
        $output = new SpyOutput();
        $this->strava->setMaxNumberOfCallsBeforeTriggering429(3);

        $this->getContainer()->get(ChallengeRepository::class)->add(
            ChallengeBuilder::fromDefaults()
                ->withChallengeId('654321')
                ->build()
        );

        $this->commandBus->dispatch(new ImportChallenges($output));

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
