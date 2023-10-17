<?php

namespace App\Tests;

use App\Domain\Strava\Activity\StravaActivityRepository;
use App\Infrastructure\DependencyInjection\ContainerFactory;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Lcobucci\Clock\Clock;
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
        self::$container->get(StravaActivityRepository::class)::$cachedActivities = [];
    }

    public function bootContainer(): ContainerInterface
    {
        if (!self::$container) {
            self::$container = ContainerFactory::createForTestSuite();
            self::$container->set(FilesystemOperator::class, new SpyFileSystem());
            self::$container->set(
                name: Clock::class,
                value: PausedClock::on(SerializableDateTime::fromString('2023-10-17 16:15:04'))
            );
        }

        return self::$container;
    }

    public function getContainer(): ContainerInterface
    {
        return self::$container;
    }
}
