<?php

namespace App\Tests\Domain\Strava\Challenge;

use App\Domain\Strava\Challenge\ChallengeCollection;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\Challenge\DbalChallengeRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\DatabaseTestCase;

class DbalChallengeRepositoryTest extends DatabaseTestCase
{
    private ChallengeRepository $challengeRepository;

    public function testFindAndSave(): void
    {
        $challenge = ChallengeBuilder::fromDefaults()->build();
        $this->challengeRepository->add($challenge);

        $this->assertEquals(
            $challenge,
            $this->challengeRepository->find($challenge->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->challengeRepository->find('1');
    }

    public function testFindAll(): void
    {
        $challengeOne = ChallengeBuilder::fromDefaults()
            ->withChallengeId('1')
            ->withCreatedOn(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->challengeRepository->add($challengeOne);
        $challengeTwo = ChallengeBuilder::fromDefaults()
            ->withChallengeId('2')
            ->withCreatedOn(SerializableDateTime::fromString('2023-10-10 15:00:34'))
            ->build();
        $this->challengeRepository->add($challengeTwo);

        $this->assertEquals(
            ChallengeCollection::fromArray([$challengeTwo, $challengeOne]),
            $this->challengeRepository->findAll()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->challengeRepository = new DbalChallengeRepository(
            $this->getConnection()
        );
    }
}
