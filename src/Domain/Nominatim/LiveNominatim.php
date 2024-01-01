<?php

declare(strict_types=1);

namespace App\Domain\Nominatim;

use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final readonly class LiveNominatim implements Nominatim
{
    public function __construct(
        private Client $client,
    ) {
    }

    public function reverseGeocode(Coordinate $coordinate): Address
    {
        $response = $this->client->request(
            'GET',
            'https://nominatim.openstreetmap.org/reverse',
            [
                RequestOptions::QUERY => [
                    'lat' => $coordinate->getLatitude()->toFloat(),
                    'lon' => $coordinate->getLongitude()->toFloat(),
                    'format' => 'json',
                ],
            ]
        );

        $response = Json::decode($response->getBody()->getContents());

        return Address::fromState($response['address']);
    }
}
