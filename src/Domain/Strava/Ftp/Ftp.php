<?php

declare(strict_types=1);

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Weight;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class Ftp
{
    private ?Weight $athleteWeight = null;

    private function __construct(
        #[ORM\Id, ORM\Column(type: 'date_immutable')]
        private readonly SerializableDateTime $setOn,
        #[ORM\Column(type: 'integer')]
        private readonly FtpValue $ftp,
    ) {
    }

    public static function fromState(
        SerializableDateTime $setOn,
        FtpValue $ftp,
    ): self {
        return new self(
            setOn: $setOn,
            ftp: $ftp
        );
    }

    public function getSetOn(): SerializableDateTime
    {
        return $this->setOn;
    }

    public function getFtp(): FtpValue
    {
        return $this->ftp;
    }

    public function getRelativeFtp(): ?float
    {
        if (!$this->athleteWeight) {
            return null;
        }

        return round($this->getFtp()->getValue() / $this->athleteWeight->getFloat(), 1);
    }

    public function enrichWithAthleteWeight(?Weight $athleteWeight): void
    {
        $this->athleteWeight = $athleteWeight;
    }
}
