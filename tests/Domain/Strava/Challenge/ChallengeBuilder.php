<?php

declare(strict_types=1);

namespace App\Tests\Domain\Strava\Challenge;

use App\Domain\Strava\Challenge\Challenge;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class ChallengeBuilder
{
    private string $challengeId;
    private SerializableDateTime $createdOn;
    private array $data;

    private function __construct()
    {
        $this->challengeId = 'test';
        $this->createdOn = SerializableDateTime::fromString('2023-10-10');
        $this->data = [];
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): Challenge
    {
        return Challenge::fromState(
            challengeId: $this->challengeId,
            createdOn: $this->createdOn,
            data: $this->data,
        );
    }

    public function withChallengeId(string $challengeId): self
    {
        $this->challengeId = $challengeId;

        return $this;
    }

    public function withCreatedOn(SerializableDateTime $createdOn): self
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    public function withData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
