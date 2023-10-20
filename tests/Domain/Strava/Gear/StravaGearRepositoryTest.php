<?php

namespace App\Tests\Domain\Strava\Gear;

use App\Domain\Strava\Gear\GearCollection;
use App\Domain\Strava\Gear\StravaGearRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Tests\DatabaseTestCase;

class StravaGearRepositoryTest extends DatabaseTestCase
{
    private StravaGearRepository $stravaGearRepository;

    public function testFindAndSave(): void
    {
        $gear = GearBuilder::fromDefaults()
            ->withGearId(1)
            ->withDistanceInMeter(1230)
            ->build();
        $this->stravaGearRepository->add($gear);

        $this->assertEquals(
            $gear,
            $this->stravaGearRepository->find($gear->getId())
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);
        $this->stravaGearRepository->find('1');
    }

    public function testFindAll(): void
    {
        $gearOne = GearBuilder::fromDefaults()
            ->withGearId(1)
            ->withDistanceInMeter(1230)
            ->build();
        $this->stravaGearRepository->add($gearOne);
        $gearTwo = GearBuilder::fromDefaults()
            ->withGearId(2)
            ->withDistanceInMeter(10230)
            ->build();
        $this->stravaGearRepository->add($gearTwo);
        $gearThree = GearBuilder::fromDefaults()
            ->withGearId(3)
            ->withDistanceInMeter(230)
            ->build();
        $this->stravaGearRepository->add($gearThree);

        $this->assertEquals(
            GearCollection::fromArray([$gearTwo, $gearOne, $gearThree]),
            $this->stravaGearRepository->findAll()
        );
    }

    public function testUpdate(): void
    {
        $gear = GearBuilder::fromDefaults()
            ->withGearId(1)
            ->withDistanceInMeter(1000)
            ->build();
        $this->stravaGearRepository->add($gear);

        $this->assertEquals(
            1000,
            $gear->getDistanceInMeter()
        );

        $gear->updateDistance(30000, 30.00);
        $this->stravaGearRepository->update($gear);

        $this->assertEquals(
            30000,
            $this->stravaGearRepository->find(1)->getDistanceInMeter()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->stravaGearRepository = new StravaGearRepository(
            $this->getConnection()
        );
    }
}
