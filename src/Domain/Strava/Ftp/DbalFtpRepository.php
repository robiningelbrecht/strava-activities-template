<?php

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalFtpRepository implements FtpRepository
{
    public function __construct(
        private Connection $connection
    ) {
    }

    public function findAll(): FtpCollection
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Ftp')
            ->orderBy('setOn', 'ASC');

        return FtpCollection::fromArray(array_map(
            fn (array $result) => $this->buildFromResult($result),
            $queryBuilder->executeQuery()->fetchAllAssociative()
        ));
    }

    public function find(SerializableDateTime $dateTime): Ftp
    {
        $dateTime = SerializableDateTime::fromString($dateTime->format('Y-m-d'));
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

    public function save(Ftp $ftp): void
    {
        $sql = 'REPLACE INTO Ftp (setOn, ftp)
        VALUES (:setOn, :ftp)';

        $this->connection->executeStatement($sql, [
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
            setOn: SerializableDateTime::fromString($result['setOn']),
            ftp: FtpValue::fromInt((int) $result['ftp'])
        );
    }
}
