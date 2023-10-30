<?php

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface FtpRepository
{
    public function findAll(): FtpCollection;

    public function find(SerializableDateTime $dateTime): Ftp;

    public function save(Ftp $ftp): void;
}
