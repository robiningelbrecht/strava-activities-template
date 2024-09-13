<?php

namespace App\Domain\Strava\Ftp\ReadModel;

use App\Domain\Strava\Ftp\Ftp;
use App\Domain\Strava\Ftp\Ftps;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface FtpDetailsRepository
{
    public function findAll(): Ftps;

    public function find(SerializableDateTime $dateTime): Ftp;
}
