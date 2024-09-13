<?php

namespace App\Domain\Strava\Ftp\ReadModel;

use App\Domain\Strava\Ftp\Ftp;
use App\Domain\Strava\Ftp\Ftps;
use App\Domain\Strava\Ftp\FtpValue;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

final readonly class DbalFtpDetailsRepository implements FtpDetailsRepository
{
    private Connection $connection;

    public function __construct(
        ConnectionFactory $connectionFactory,
    ) {
        $this->connection = $connectionFactory->getReadOnly();
    }

    public function findAll(): Ftps
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from('Ftp')
            ->orderBy('setOn', 'ASC');

        return Ftps::fromArray(array_map(
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
