<?php

namespace App\Tests;

use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Infrastructure\DependencyInjection\ContainerFactory;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

abstract class ContainerTestCase extends TestCase
{
    private static ?ContainerInterface $container = null;

    protected function setUp(): void
    {
        parent::setUp();
        self::$container = $this->bootContainer();
        // Empty the static cache of the activity repository between tests.
        self::$container->get(ActivityDetailsRepository::class)::$cachedActivities = [];
        self::$container->get(FilesystemOperator::class)->resetWrites();
    }

    public function bootContainer(): ContainerInterface
    {
        if (!self::$container) {
            self::$container = ContainerFactory::createForTestSuite();
        }

        return self::$container;
    }

    public function getContainer(): ContainerInterface
    {
        return self::$container;
    }
}
