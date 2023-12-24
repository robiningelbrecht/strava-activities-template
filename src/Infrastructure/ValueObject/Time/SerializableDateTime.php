<?php

namespace App\Infrastructure\ValueObject\Time;

class SerializableDateTime extends \DateTimeImmutable implements \JsonSerializable, \Stringable
{
    public static function createFromFormat(string $format, string $datetime, $timezone = null): self
    {
        if (!$datetime = parent::createFromFormat($format, $datetime, $timezone)) {
            throw new \InvalidArgumentException(sprintf('Invalid date format %s for %s', $format, $datetime));
        }

        return self::fromString(
            $datetime->format('Y-m-d H:i:s')
        );
    }

    public static function fromString(string $string): self
    {
        return new self($string);
    }

    public static function fromYearAndWeekNumber(int $year, int $weekNumber): self
    {
        $datetime = (new self())->setISODate($year, $weekNumber);

        return self::fromString(
            $datetime->format('Y-m-d H:i:s')
        );
    }

    public static function fromDateTimeImmutable(\DateTimeImmutable $date): self
    {
        return self::fromString($date->format('Y-m-d H:i:s'));
    }

    public function __toString(): string
    {
        return $this->format('Y-m-d H:i:s');
    }

    public function jsonSerialize(): string
    {
        return $this->format('Y-m-d H:i:s');
    }

    public function getMinutesSinceStartOfDay(): int
    {
        return ($this->getHourWithoutLeadingZero() * 60) + $this->getMinutesWithoutLeadingZero();
    }

    public function getHourWithoutLeadingZero(): int
    {
        return (int) $this->format('G');
    }

    public function getMinutesWithoutLeadingZero(): int
    {
        return intval($this->format('i'));
    }

    public function getMonthWithoutLeadingZero(): int
    {
        return intval($this->format('n'));
    }

    public function getWeekNumber(): int
    {
        $weekNumber = (int) $this->format('W');
        if ($weekNumber > 51 && 1 === $this->getMonthWithoutLeadingZero()) {
            $weekNumber = 1;
        }
        if ($weekNumber > 51 && 12 === $this->getMonthWithoutLeadingZero()) {
            $weekNumber = 51;
        }

        return $weekNumber;
    }

    public function getYearAndWeekNumber(): string
    {
        return $this->format('Y').'-'.$this->getWeekNumber();
    }

    public function isAfterOrOn(SerializableDateTime $that): bool
    {
        return $this >= $that;
    }

    public function isBeforeOrOn(SerializableDateTime $that): bool
    {
        return $this <= $that;
    }

    public function isBefore(SerializableDateTime $that): bool
    {
        return !$this->isAfterOrOn($that);
    }

    public function isAfter(SerializableDateTime $that): bool
    {
        return !$this->isBeforeOrOn($that);
    }
}
