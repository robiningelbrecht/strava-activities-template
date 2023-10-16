<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ActivityStream')]
final class DefaultStream implements ActivityStream
{
    private array $bestAverageForTimeIntervals = [];

    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private readonly string $activityId,
        #[ORM\Id, ORM\Column(type: 'string')]
        private readonly StreamType $streamType,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $createdOn,
        #[ORM\Column(type: 'json')]
        private readonly array $data
    ) {
    }

    public static function create(
        string $activityId,
        StreamType $streamType,
        array $streamData,
        SerializableDateTime $createdOn
    ): self {
        return new self(
            activityId: $activityId,
            streamType: $streamType,
            createdOn: $createdOn,
            data: $streamData,
        );
    }

    public static function fromState(
        string $activityId,
        StreamType $streamType,
        array $streamData,
        SerializableDateTime $createdOn
    ): self {
        return new self(
            activityId: $activityId,
            streamType: $streamType,
            createdOn: $createdOn,
            data: $streamData,
        );
    }

    public function getName(): string
    {
        return $this->getActivityId().' - '.$this->getStreamType()->value;
    }

    public function getCreatedOn(): SerializableDateTime
    {
        return $this->createdOn;
    }

    public function getActivityId(): string
    {
        return $this->activityId;
    }

    public function getStreamType(): StreamType
    {
        return $this->streamType;
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
}
