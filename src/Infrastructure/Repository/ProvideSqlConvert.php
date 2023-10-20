<?php

namespace App\Infrastructure\Repository;

use App\Infrastructure\ValueObject\Collection;

trait ProvideSqlConvert
{
    /**
     * @param \Stringable[]|string[]|int[] $values
     */
    public function toWhereInValue(array $values): string
    {
        return '"'.\implode('","', $values).'"';
    }

    /**
     * @param \BackedEnum[] $values
     */
    public function toWhereInValueForEnums(array $values): string
    {
        return $this->toWhereInValue(array_map(fn (\BackedEnum $enum) => $enum->value, $values));
    }

    public function toWhereInValueForCollection(Collection $values): string
    {
        return $this->toWhereInValue(array_map(fn (\BackedEnum $enum) => $enum->value, $values->toArray()));
    }
}
