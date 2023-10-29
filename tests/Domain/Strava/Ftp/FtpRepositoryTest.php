<?php

namespace App\Tests\Domain\Strava\Ftp;

use App\Domain\Strava\Ftp\FtpRepository;
use App\Domain\Strava\Ftp\FtpValue;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\DatabaseTestCase;
use Ramsey\Uuid\Uuid;

class FtpRepositoryTest extends DatabaseTestCase
{
    private FtpRepository $ftpRepository;

    public function testFindForDate(): void
    {
        $ftpOne = FtpBuilder::fromDefaults()
            ->withFtpId(Uuid::uuid4())
            ->withSetOn(SerializableDateTime::fromString('2023-04-01'))
            ->withFtp(FtpValue::fromInt(198))
            ->build();
        $this->ftpRepository->add($ftpOne);
        $ftpTwo = FtpBuilder::fromDefaults()
            ->withFtpId(Uuid::uuid4())
            ->withSetOn(SerializableDateTime::fromString('2023-05-25'))
            ->withFtp(FtpValue::fromInt(220))
            ->build();
        $this->ftpRepository->add($ftpTwo);
        $ftpThree = FtpBuilder::fromDefaults()
            ->withFtpId(Uuid::uuid4())
            ->withSetOn(SerializableDateTime::fromString('2023-08-01'))
            ->withFtp(FtpValue::fromInt(238))
            ->build();
        $this->ftpRepository->add($ftpThree);
        $ftpFour = FtpBuilder::fromDefaults()
            ->withFtpId(Uuid::uuid4())
            ->withSetOn(SerializableDateTime::fromString('2023-09-24'))
            ->withFtp(FtpValue::fromInt(250))
            ->build();
        $this->ftpRepository->add($ftpFour);

        $this->assertEquals(
            $ftpOne,
            $this->ftpRepository->findForDate(SerializableDateTime::fromString('2023-05-24'))
        );
        $this->assertEquals(
            $ftpTwo,
            $this->ftpRepository->findForDate(SerializableDateTime::fromString('2023-05-25'))
        );
        $this->assertEquals(
            $ftpTwo,
            $this->ftpRepository->findForDate(SerializableDateTime::fromString('2023-06-25'))
        );
        $this->assertEquals(
            $ftpThree,
            $this->ftpRepository->findForDate(SerializableDateTime::fromString('2023-08-04'))
        );
        $this->assertEquals(
            $ftpFour,
            $this->ftpRepository->findForDate(SerializableDateTime::fromString('2023-09-24'))
        );
        $this->assertEquals(
            $ftpFour,
            $this->ftpRepository->findForDate(SerializableDateTime::fromString('2023-10-24'))
        );
    }

    public function testItShouldThrowWhenNotFound(): void
    {
        $ftpOne = FtpBuilder::fromDefaults()
            ->withFtpId(Uuid::uuid4())
            ->withSetOn(SerializableDateTime::fromString('2023-04-01'))
            ->withFtp(FtpValue::fromInt(198))
            ->build();
        $this->ftpRepository->add($ftpOne);
        $ftpTwo = FtpBuilder::fromDefaults()
            ->withFtpId(Uuid::uuid4())
            ->withSetOn(SerializableDateTime::fromString('2023-05-25'))
            ->withFtp(FtpValue::fromInt(220))
            ->build();
        $this->ftpRepository->add($ftpTwo);
        $ftpThree = FtpBuilder::fromDefaults()
            ->withFtpId(Uuid::uuid4())
            ->withSetOn(SerializableDateTime::fromString('2023-08-01'))
            ->withFtp(FtpValue::fromInt(238))
            ->build();
        $this->ftpRepository->add($ftpThree);
        $ftpFour = FtpBuilder::fromDefaults()
            ->withFtpId(Uuid::uuid4())
            ->withSetOn(SerializableDateTime::fromString('2023-09-24'))
            ->withFtp(FtpValue::fromInt(250))
            ->build();
        $this->ftpRepository->add($ftpFour);

        $this->expectException(EntityNotFound::class);

        $this->ftpRepository->findForDate(SerializableDateTime::fromString('2023-01-01'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ftpRepository = new FtpRepository(
            $this->getConnection()
        );
    }
}
