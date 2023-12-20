<?php

namespace App\Domain\Strava\Ftp\WriteModel;

use App\Domain\Strava\Ftp\Ftp;

interface FtpRepository
{
    public function save(Ftp $ftp): void;
}
