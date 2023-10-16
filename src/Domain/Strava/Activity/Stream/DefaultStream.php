<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ActivityStream')]
final class DefaultStream implements ActivityStream
{
    /** @var array<mixed> */
    private array $bestAverageForTimeIntervals = [];

    /**
     * @param array<mixed> $data
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private readonly int $activityId,
        #[ORM\Id, ORM\Column(type: 'string')]
        private readonly StreamType $streamType,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $createdOn,
        #[ORM\Column(type: 'json')]
        private readonly array $data
    ) {
    }

    /**
     * @param array<mixed> $streamData
     */
    public static function create(
        int $activityId,
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

    /**
     * @param array<mixed> $streamData
     */
    public static function fromState(
        int $activityId,
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

    public function getActivityId(): int
    {
        return $this->activityId;
    }

    public function getStreamType(): StreamType
    {
        return $this->streamType;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
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

        $this->bestAverageForTimeIntervals[$timeIntervalInSeconds] = (int) round(array_sum($bestSequence) / $timeIntervalInSeconds);

        return $this->bestAverageForTimeIntervals[$timeIntervalInSeconds];
    }

    /**
     * @return array<mixed>
     */
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
