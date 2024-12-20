<?php

namespace App\Tests\Domain\Strava\Ftp;

use App\Domain\Strava\Ftp\Ftps;
use App\Domain\Strava\Ftp\FtpValue;
use App\Domain\Strava\Ftp\ReadModel\DbalFtpDetailsRepository;
use App\Domain\Strava\Ftp\ReadModel\FtpDetailsRepository;
use App\Domain\Strava\Ftp\WriteModel\DbalFtpRepository;
use App\Domain\Strava\Ftp\WriteModel\FtpRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\DatabaseTestCase;

class DbalFtpRepositoryTest extends DatabaseTestCase
{
    private FtpRepository $ftpRepository;
    private FtpDetailsRepository $ftpDetailsRepository;

    public function testFindForDate(): void
    {
        $ftpOne = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-04-01'))
            ->withFtp(FtpValue::fromInt(198))
            ->build();
        $this->ftpRepository->save($ftpOne);
        $ftpTwo = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-05-25'))
            ->withFtp(FtpValue::fromInt(220))
            ->build();
        $this->ftpRepository->save($ftpTwo);
        $ftpThree = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-08-01'))
            ->withFtp(FtpValue::fromInt(238))
            ->build();
        $this->ftpRepository->save($ftpThree);
        $ftpFour = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-09-24'))
            ->withFtp(FtpValue::fromInt(250))
            ->build();
        $this->ftpRepository->save($ftpFour);

        $this->assertEquals(
            $ftpOne,
            $this->ftpDetailsRepository->find(SerializableDateTime::fromString('2023-05-24'))
        );
        $this->assertEquals(
            $ftpTwo,
            $this->ftpDetailsRepository->find(SerializableDateTime::fromString('2023-05-25'))
        );
        $this->assertEquals(
            $ftpTwo,
            $this->ftpDetailsRepository->find(SerializableDateTime::fromString('2023-06-25'))
        );
        $this->assertEquals(
            $ftpThree,
            $this->ftpDetailsRepository->find(SerializableDateTime::fromString('2023-08-04'))
        );
        $this->assertEquals(
            $ftpFour,
            $this->ftpDetailsRepository->find(SerializableDateTime::fromString('2023-09-24'))
        );
        $this->assertEquals(
            $ftpFour,
            $this->ftpDetailsRepository->find(SerializableDateTime::fromString('2023-10-24'))
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $ftpOne = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-04-01'))
            ->withFtp(FtpValue::fromInt(198))
            ->build();
        $this->ftpRepository->save($ftpOne);
        $ftpTwo = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-05-25'))
            ->withFtp(FtpValue::fromInt(220))
            ->build();
        $this->ftpRepository->save($ftpTwo);
        $ftpThree = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-08-01'))
            ->withFtp(FtpValue::fromInt(238))
            ->build();
        $this->ftpRepository->save($ftpThree);
        $ftpFour = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-09-24'))
            ->withFtp(FtpValue::fromInt(250))
            ->build();
        $this->ftpRepository->save($ftpFour);

        $this->expectException(EntityNotFound::class);

        $this->ftpDetailsRepository->find(SerializableDateTime::fromString('2023-01-01'));
    }

    public function testFindAll(): void
    {
        $ftpOne = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-04-01'))
            ->withFtp(FtpValue::fromInt(198))
            ->build();
        $this->ftpRepository->save($ftpOne);
        $ftpTwo = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-05-25'))
            ->withFtp(FtpValue::fromInt(220))
            ->build();
        $this->ftpRepository->save($ftpTwo);
        $ftpThree = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-08-01'))
            ->withFtp(FtpValue::fromInt(238))
            ->build();
        $this->ftpRepository->save($ftpThree);
        $ftpFour = FtpBuilder::fromDefaults()
            ->withSetOn(SerializableDateTime::fromString('2023-09-24'))
            ->withFtp(FtpValue::fromInt(250))
            ->build();
        $this->ftpRepository->save($ftpFour);

        $this->assertEquals(
            Ftps::fromArray([$ftpOne, $ftpTwo, $ftpThree, $ftpFour]),
            $this->ftpDetailsRepository->findAll()
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ftpRepository = new DbalFtpRepository(
            $this->getConnectionFactory()
        );
        $this->ftpDetailsRepository = new DbalFtpDetailsRepository(
            $this->getConnectionFactory()
        );
    }
}
