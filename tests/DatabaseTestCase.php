<?php

namespace App\Tests;

use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Environment\Settings;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;

abstract class DatabaseTestCase extends ContainerTestCase
{
    protected static ?ConnectionFactory $connectionFactory = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$connectionFactory) {
            self::$connectionFactory = $this->getContainer()->get(ConnectionFactory::class);
        }

        $this->createTestDatabases();
    }

    public function getConnectionFactory(): ?ConnectionFactory
    {
        return self::$connectionFactory;
    }

    private function createTestDatabases(): void
    {
        $container = $this->getContainer();
        /** @var Settings $settings */
        $settings = $container->get(Settings::class);
        /** @var ConnectionFactory $connectionFactory */
        $connectionFactory = $container->get(ConnectionFactory::class);

        $entityManager = new EntityManager(
            conn: $connectionFactory->getDefault(),
            config: ORMSetup::createAttributeMetadataConfiguration(
                $settings->get('doctrine.metadata_dirs'),
                $settings->get('doctrine.dev_mode'),
            )
        );

        $schemaTool = new SchemaTool($entityManager);
        $classes = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($classes);
    }
}
