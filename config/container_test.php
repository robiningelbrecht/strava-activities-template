<?php

use App\Domain\Nominatim\Nominatim;
use App\Domain\Strava\Strava;
use App\Domain\Weather\OpenMeteo\OpenMeteo;
use App\Infrastructure\Doctrine\Connection\ConnectionFactory;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use App\Infrastructure\Time\Sleep;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\UuidFactory;
use App\Tests\Domain\Nominatim\SpyNominatim;
use App\Tests\Domain\Strava\SpyStrava;
use App\Tests\Domain\Weather\OpenMeteo\SpyOpenMeteo;
use App\Tests\FakeConnectionFactory;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use App\Tests\Infrastructure\ValueObject\FakeUuidFactory;
use App\Tests\NullSleep;
use App\Tests\PausedClock;
use App\Tests\SpyFileSystem;
use Lcobucci\Clock\Clock;
use League\Flysystem\FilesystemOperator;

return [
    Strava::class => new SpyStrava(),
    Clock::class => PausedClock::on(SerializableDateTime::fromString('2023-10-17 16:15:04')),
    Sleep::class => new NullSleep(),
    UuidFactory::class => new FakeUuidFactory(),
    FilesystemOperator::class => fn () => new SpyFileSystem(),
    OpenMeteo::class => new SpyOpenMeteo(),
    Nominatim::class => new SpyNominatim(),
    ConnectionFactory::class => new FakeConnectionFactory(),
    ResourceUsage::class => new FixedResourceUsage(),
];
