<?php

namespace App\Domain\Strava\Challenge;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class Challenge implements \JsonSerializable
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private readonly string $challengeId,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $createdOn,
        #[ORM\Column(type: 'json')]
        private array $data
    ) {
    }

    public static function fromMap(array $data): self
    {
        return new self($data);
    }

    public static function create(array $data, SerializableDateTime $createdOn): self
    {
        $data['createdOn'] = $createdOn->getTimestamp();

        return new self($data);
    }

    public function getId(): string
    {
        return $this->data['challenge_id'];
    }

    public function getName(): string
    {
        return $this->data['name'];
    }

    public function getLogoUrl(): ?string
    {
        return $this->data['logo_url'] ?? null;
    }

    public function getLocalLogoUrl(): ?string
    {
        return $this->data['localLogo'] ?? null;
    }

    public function getRemoteImagePath(): ?string
    {
        if (!$this->getLocalLogoUrl()) {
            return null;
        }

        return 'https://raw.githubusercontent.com/'.$_ENV['REPOSITORY_NAME'].'/master/'.$this->getLocalLogoUrl();
    }

    public function getUrl(): string
    {
        return 'https://www.strava.com/challenges/'.$this->data['url'];
    }

    public function updateLocalLogo(string $path): void
    {
        $this->data['localLogo'] = $path;
    }

    public function getCreatedOn(): SerializableDateTime
    {
        return $this->createdOn;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
