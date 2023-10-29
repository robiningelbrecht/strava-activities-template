<?php

declare(strict_types=1);

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
final readonly class Ftp
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private UuidInterface $ftpId,
        #[ORM\Column(type: 'datetime_immutable')]
        private SerializableDateTime $setOn,
        #[ORM\Column(type: 'integer')]
        private int $ftp,
    ) {
    }

    public static function fromState(
        UuidInterface $ftpId,
        SerializableDateTime $setOn,
        int $ftp,
    ): self {
        return new self(
            ftpId: $ftpId,
            setOn: $setOn,
            ftp: $ftp
        );
    }

    public function getFtpId(): UuidInterface
    {
        return $this->ftpId;
    }

    public function getSetOn(): SerializableDateTime
    {
        return $this->setOn;
    }

    public function getFtp(): int
    {
        return $this->ftp;
    }
}
