<?php

namespace App\Tests\Domain\Nominatim;

use App\Domain\Nominatim\LiveNominatim;
use App\Domain\Nominatim\Nominatim;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use App\Infrastructure\ValueObject\Geography\Latitude;
use App\Infrastructure\ValueObject\Geography\Longitude;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class LiveNominatimTest extends TestCase
{
    use MatchesSnapshots;

    private Nominatim $nominatim;
    private MockObject $client;

    public function testReverseGeocode(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('GET', $method);
                $this->assertEquals('https://nominatim.openstreetmap.org/reverse', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, [], Json::encode([
                    'address' => [],
                ]));
            });

        $this->nominatim->reverseGeocode(
            Coordinate::createFromLatAndLng(
                latitude: Latitude::fromString('80'),
                longitude: Longitude::fromString('100'),
            )
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Client::class);

        $this->nominatim = new LiveNominatim(
            $this->client,
        );
    }
}
