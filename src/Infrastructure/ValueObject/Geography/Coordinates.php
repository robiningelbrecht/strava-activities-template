<?php

namespace App\Infrastructure\ValueObject\Geography;

final readonly class Coordinates
{
    private function __construct(
        private Latitude $latitude,
        private Longitude $longitude)
    {
    }

    public static function createFromLatAndLng(Latitude $latitude, Longitude $longitude): Coordinates
    {
        return new self(
            $latitude,
            $longitude
        );
    }

    public function getLatitude(): Latitude
    {
        return $this->latitude;
    }

    public function getLongitude(): Longitude
    {
        return $this->longitude;
    }
}
