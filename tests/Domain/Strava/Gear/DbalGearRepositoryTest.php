<?php

namespace App\Tests\Domain\Strava\Gear;

use App\Domain\Strava\Gear\DbalGearRepository;
use App\Domain\Strava\Gear\GearCollection;
use App\Domain\Strava\Gear\GearRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Tests\DatabaseTestCase;

class DbalGearRepositoryTest extends DatabaseTestCase
{
    private GearRepository $gearRepository;

    public function testFindAndSave(): void
    {
        $gear = GearBuilder::fromDefaults()
            ->withGearId(1)
            ->withDistanceInMeter(1230)
            ->build();
        $this->gearRepository->add($gear);

        $this->assertEquals(
            $gear,
            $this->gearRepository->find($gear->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->gearRepository->find('1');
    }

    public function testFindAll(): void
    {
        $gearOne = GearBuilder::fromDefaults()
            ->withGearId(1)
            ->withDistanceInMeter(1230)
            ->build();
        $this->gearRepository->add($gearOne);
        $gearTwo = GearBuilder::fromDefaults()
            ->withGearId(2)
            ->withDistanceInMeter(10230)
            ->build();
        $this->gearRepository->add($gearTwo);
        $gearThree = GearBuilder::fromDefaults()
            ->withGearId(3)
            ->withDistanceInMeter(230)
            ->build();
        $this->gearRepository->add($gearThree);

        $this->assertEquals(
            GearCollection::fromArray([$gearTwo, $gearOne, $gearThree]),
            $this->gearRepository->findAll()
        );
    }

    public function testUpdate(): void
    {
        $gear = GearBuilder::fromDefaults()
            ->withGearId(1)
            ->withDistanceInMeter(1000)
            ->build();
        $this->gearRepository->add($gear);

        $this->assertEquals(
            1000,
            $gear->getDistanceInMeter()
        );

        $gear->updateDistance(30000, 30.00);
        $this->gearRepository->update($gear);

        $this->assertEquals(
            30000,
            $this->gearRepository->find(1)->getDistanceInMeter()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->gearRepository = new DbalGearRepository(
            $this->getConnection()
        );
    }
}
