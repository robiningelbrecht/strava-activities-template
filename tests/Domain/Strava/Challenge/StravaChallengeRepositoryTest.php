<?php

namespace App\Tests\Domain\Strava\Challenge;

use App\Domain\Strava\Challenge\ChallengeCollection;
use App\Domain\Strava\Challenge\StravaChallengeRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\DatabaseTestCase;

class StravaChallengeRepositoryTest extends DatabaseTestCase
{
    private StravaChallengeRepository $stravaChallengeRepository;

    public function testFindAndSave(): void
    {
        $challenge = ChallengeBuilder::fromDefaults()->build();
        $this->stravaChallengeRepository->add($challenge);

        $this->assertEquals(
            $challenge,
            $this->stravaChallengeRepository->find($challenge->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->stravaChallengeRepository->find('1');
    }

    public function testFindAll(): void
    {
        $challengeOne = ChallengeBuilder::fromDefaults()
            ->withChallengeId('1')
            ->withCreatedOn(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->stravaChallengeRepository->add($challengeOne);
        $challengeTwo = ChallengeBuilder::fromDefaults()
            ->withChallengeId('2')
            ->withCreatedOn(SerializableDateTime::fromString('2023-10-10 15:00:34'))
            ->build();
        $this->stravaChallengeRepository->add($challengeTwo);

        $this->assertEquals(
            ChallengeCollection::fromArray([$challengeTwo, $challengeOne]),
            $this->stravaChallengeRepository->findAll()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->stravaChallengeRepository = new StravaChallengeRepository(
            $this->getConnection()
        );
    }
}
