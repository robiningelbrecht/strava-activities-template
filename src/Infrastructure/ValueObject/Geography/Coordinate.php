<?php

namespace App\Infrastructure\ValueObject\Geography;

final readonly class Coordinate implements \JsonSerializable
{
    private function __construct(
        private Latitude $latitude,
        private Longitude $longitude)
    {
    }

    public static function createFromLatAndLng(Latitude $latitude, Longitude $longitude): Coordinate
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

    /**
     * @return \App\Infrastructure\ValueObject\Geography\FloatLiteral[]
     */
    public function jsonSerialize(): array
    {
        return [$this->getLatitude(), $this->getLongitude()];
    }
}
