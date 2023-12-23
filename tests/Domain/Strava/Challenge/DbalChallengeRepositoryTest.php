<?php

namespace App\Tests\Domain\Strava\Challenge;

use App\Domain\Strava\Challenge\ChallengeCollection;
use App\Domain\Strava\Challenge\ChallengeId;
use App\Domain\Strava\Challenge\ReadModel\ChallengeDetailsRepository;
use App\Domain\Strava\Challenge\ReadModel\DbalChallengeDetailsRepository;
use App\Domain\Strava\Challenge\WriteModel\ChallengeRepository;
use App\Domain\Strava\Challenge\WriteModel\DbalChallengeRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\DatabaseTestCase;

class DbalChallengeRepositoryTest extends DatabaseTestCase
{
    private ChallengeRepository $challengeRepository;
    private ChallengeDetailsRepository $challengeDetailsRepository;

    public function testFindAndSave(): void
    {
        $challenge = ChallengeBuilder::fromDefaults()->build();
        $this->challengeRepository->add($challenge);

        $this->assertEquals(
            $challenge,
            $this->challengeDetailsRepository->find($challenge->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->challengeDetailsRepository->find(ChallengeId::fromUnprefixed('1'));
    }

    public function testFindAll(): void
    {
        $challengeOne = ChallengeBuilder::fromDefaults()
            ->withChallengeId(ChallengeId::fromUnprefixed('1'))
            ->withCreatedOn(SerializableDateTime::fromString('2023-10-10 14:00:34'))
            ->build();
        $this->challengeRepository->add($challengeOne);
        $challengeTwo = ChallengeBuilder::fromDefaults()
            ->withChallengeId(ChallengeId::fromUnprefixed('2'))
            ->withCreatedOn(SerializableDateTime::fromString('2023-10-10 15:00:34'))
            ->build();
        $this->challengeRepository->add($challengeTwo);

        $this->assertEquals(
            ChallengeCollection::fromArray([$challengeTwo, $challengeOne]),
            $this->challengeDetailsRepository->findAll()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->challengeRepository = new DbalChallengeRepository(
            $this->getConnectionFactory()
        );
        $this->challengeDetailsRepository = new DbalChallengeDetailsRepository(
            $this->getConnectionFactory()
        );
    }
}
