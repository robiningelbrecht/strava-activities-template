<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class DefaultStream implements ActivityStream
{
    private array $bestAverageForTimeIntervals = [];

    private function __construct(
        private readonly array $data
    ) {
    }

    public static function create(
        string $activityId,
        StreamType $streamType,
        array $streamData,
        SerializableDateTime $createdOn
    ): self {
        return new self([
            'type' => $streamType->value,
            'activityId' => $activityId,
            'data' => $streamData,
            'createdOn' => $createdOn->getTimestamp(),
        ]);
    }

    public static function fromMap(array $data): self
    {
        return new self($data);
    }

    public function getName(): string
    {
        return $this->data['activityId'].' - '.$this->getType()->value;
    }

    public function getActivityId(): string
    {
        return $this->data['activityId'];
    }

    public function getType(): StreamType
    {
        return StreamType::from($this->data['type']);
    }

    public function getData(): array
    {
        return $this->data['data'] ?? [];
    }

    public function getBestAverageForTimeInterval(int $timeIntervalInSeconds): ?int
    {
        if (array_key_exists($timeIntervalInSeconds, $this->bestAverageForTimeIntervals)) {
            return $this->bestAverageForTimeIntervals[$timeIntervalInSeconds];
        }

        if (!$bestSequence = $this->getBestSequence($timeIntervalInSeconds)) {
            $this->bestAverageForTimeIntervals[$timeIntervalInSeconds] = null;

            return null;
        }

        $this->bestAverageForTimeIntervals[$timeIntervalInSeconds] = round(array_sum($bestSequence) / $timeIntervalInSeconds);

        return $this->bestAverageForTimeIntervals[$timeIntervalInSeconds];
    }

    private function getBestSequence(int $sequenceLength): array
    {
        $best = 0;
        $bestSequence = [];

        $sequence = $this->getData();

        if (count($sequence) < $sequenceLength) {
            return [];
        }

        for ($i = 0; $i < count($sequence) - $sequenceLength; ++$i) {
            $copySequence = $sequence;
            $sequenceToCheck = array_slice($copySequence, $i, $sequenceLength);
            $total = array_sum($sequenceToCheck);

            if ($total > $best) {
                $best = $total;
                $bestSequence = $sequenceToCheck;
            }
        }

        return $bestSequence;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
