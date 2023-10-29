<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Ftp;

use App\Domain\Strava\Ftp\Ftp;
use App\Domain\Strava\Ftp\FtpValue;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class FtpBuilder
{
    private UuidInterface $ftpId;
    private SerializableDateTime $setOn;
    private FtpValue $ftp;

    private function __construct()
    {
        $this->ftpId = Uuid::fromString('9f17cfe9-0c80-465a-921b-55f5b391de10');
        $this->setOn = SerializableDateTime::fromString('2023-01-04');
        $this->ftp = FtpValue::fromInt(200);
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): Ftp
    {
        return Ftp::fromState(
            ftpId: $this->ftpId,
            setOn: $this->setOn,
            ftp: $this->ftp
        );
    }

    public function withFtpId(UuidInterface $ftpId): self
    {
        $this->ftpId = $ftpId;

        return $this;
    }

    public function withSetOn(SerializableDateTime $setOn): self
    {
        $this->setOn = $setOn;

        return $this;
    }

    public function withFtp(FtpValue $ftp): self
    {
        $this->ftp = $ftp;

        return $this;
    }
}
