<?php

namespace App\Domain\Strava\Ftp\WriteModel;

use App\Domain\Strava\Ftp\Ftp;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;

final readonly class DbalFtpRepository implements FtpRepository
{
    public function __construct(
        private ConnectionFactory $connectionFactory
    ) {
    }

    public function save(Ftp $ftp): void
    {
        $sql = 'REPLACE INTO Ftp (setOn, ftp)
        VALUES (:setOn, :ftp)';

        $this->connectionFactory->getDefault()->executeStatement($sql, [
            'setOn' => $ftp->getSetOn(),
            'ftp' => $ftp->getFtp(),
        ]);
    }
}
