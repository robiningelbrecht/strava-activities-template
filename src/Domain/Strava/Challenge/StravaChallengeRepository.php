<?php

namespace App\Domain\Strava\Challenge;

use App\Infrastructure\Exception\EntityNotFound;
use SleekDB\Store;

final readonly class StravaChallengeRepository
{
    public function __construct(
        private Store $store
    ) {
    }

    /**
     * @return \App\Domain\Strava\Challenge\Challenge[]
     */
    public function findAll(): array
    {
        return array_map(
            fn (array $row) => Challenge::fromMap($row),
            $this->store->findAll(['createdOn' => 'desc'])
        );
    }

    public function findOneBy(int $id): Challenge
    {
        if (!$row = $this->store->findOneBy(['challenge_id', '==', $id])) {
            throw new EntityNotFound(sprintf('Challenge "%s" not found', $id));
        }

        return Challenge::fromMap($row);
    }

    public function add(Challenge $challenge): void
    {
        $this->store->insert($challenge->jsonSerialize());
    }
}
