<?php

namespace App\Domain\Strava\Challenge\WriteModel;

use App\Domain\Strava\Challenge\Challenge;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\Year;

final readonly class DbalChallengeRepository implements ChallengeRepository
{
    public function __construct(
        private ConnectionFactory $connectionFactory
    ) {
    }

    public function add(Challenge $challenge): void
    {
        $sql = 'INSERT INTO Challenge (challengeId, createdOn, data)
        VALUES (:challengeId, :createdOn, :data)';

        $this->connectionFactory->getForYear(Year::fromDate($challenge->getCreatedOn()))->executeStatement($sql, [
            'challengeId' => (string) $challenge->getId(),
            'createdOn' => $challenge->getCreatedOn(),
            'data' => Json::encode($challenge->getData()),
        ]);
    }
}
