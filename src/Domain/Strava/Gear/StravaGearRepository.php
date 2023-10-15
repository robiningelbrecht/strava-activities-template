<?php

namespace App\Domain\Strava\Gear;

use App\Infrastructure\Exception\EntityNotFound;
use SleekDB\Store;

final readonly class StravaGearRepository
{
    public function __construct(
        private Store $store
    ) {
    }

    /**
     * @return \App\Domain\Strava\Gear\Gear[]
     */
    public function findAll(): array
    {
        return array_map(
            fn (array $row) => Gear::fromMap($row),
            $this->store->findAll(['distance' => 'desc'])
        );
    }

    public function findOneBy(string $id): Gear
    {
        if (!$row = $this->store->findOneBy(['id', '==', $id])) {
            throw new EntityNotFound(sprintf('Gear "%s" not found', $id));
        }

        return Gear::fromMap($row);
    }

    public function save(Gear $gear): void
    {
        $this->store->updateOrInsert($gear->jsonSerialize());
    }
}
