<?php

namespace App\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

abstract class DatabaseTestCase extends ContainerTestCase
{
    protected static ?Connection $connection = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$connection) {
            self::$connection = $this->getContainer()->get(Connection::class);
        }

        $this->createTestDatabase();
    }

    public function getConnection(): Connection
    {
        return self::$connection;
    }

    private function createTestDatabase(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($entityManager);
        $classes = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($classes);
    }
}
