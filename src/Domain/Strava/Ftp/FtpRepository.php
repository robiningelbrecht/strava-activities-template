<?php

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

final readonly class FtpRepository
{
    public function __construct(
        private Connection $connection
    ) {
    }

    public function findForDate(SerializableDateTime $dateTime): Ftp
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Ftp')
            ->andWhere('setOn <= :date')
            ->setParameter('date', $dateTime)
            ->setMaxResults(1)
            ->orderBy('setOn', 'DESC');

        if (!$result = $queryBuilder->executeQuery()->fetchAssociative()) {
            throw new EntityNotFound(sprintf('Ftp for date "%s" not found', $dateTime));
        }

        return $this->buildFromResult($result);
    }

    public function add(Ftp $ftp): void
    {
        $sql = 'INSERT INTO Ftp (ftpId, setOn, ftp)
        VALUES (:ftpId, :setOn, :ftp)';

        $this->connection->executeStatement($sql, [
            'ftpId' => $ftp->getFtpId(),
            'setOn' => $ftp->getSetOn(),
            'ftp' => $ftp->getFtp(),
        ]);
    }

    /**
     * @param array<mixed> $result
     */
    private function buildFromResult(array $result): Ftp
    {
        return Ftp::fromState(
            ftpId: Uuid::fromString($result['ftpId']),
            setOn: SerializableDateTime::fromString($result['setOn']),
            ftp: $result['ftp']
        );
    }
}
