<?php

namespace App\Tests\Domain\Weather\OpenMeteo;

use App\Domain\Weather\OpenMeteo\LiveOpenMeteo;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\PausedClock;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Lcobucci\Clock\Clock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class LiveOpenMeteoTest extends TestCase
{
    use MatchesSnapshots;

    private LiveOpenMeteo $liveOpenMeteo;
    private MockObject $client;
    private Clock $clock;

    public function testGetWeatherStats(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('v1/forecast', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->liveOpenMeteo->getWeatherStats(
            latitude: Latitude::fromString('80'),
            longitude: Longitude::fromString('100'),
            date: SerializableDateTime::fromString('2023-10-31'),
        );
    }

    public function testGetWeatherStatsInArchive(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('v1/archive', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([]));
            });

        $this->liveOpenMeteo->getWeatherStats(
            latitude: Latitude::fromString('80'),
            longitude: Longitude::fromString('100'),
            date: SerializableDateTime::fromString('2023-09-31'),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Client::class);
        $this->clock = PausedClock::on(SerializableDateTime::fromString('2023-10-31'));

        $this->liveOpenMeteo = new LiveOpenMeteo(
            $this->client,
            $this->clock
        );
    }
}
