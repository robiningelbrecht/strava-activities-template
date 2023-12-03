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

    public function toWhereInValueForCollection(Collection $values): string
    {
        return $this->toWhereInValue($values->map(fn (\BackedEnum $enum) => $enum->value));
    }
}
