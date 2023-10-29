<?php

declare(strict_types=1);

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final readonly class Ftp
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'date_immutable')]
        private SerializableDateTime $setOn,
        #[ORM\Column(type: 'integer')]
        private FtpValue $ftp,
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
}
