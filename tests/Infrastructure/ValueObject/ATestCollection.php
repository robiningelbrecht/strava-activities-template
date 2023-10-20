<?php

namespace App\Tests\Infrastructure\ValueObject;

use App\Infrastructure\ValueObject\Collection;
use App\Infrastructure\ValueObject\Weight;

class ATestCollection extends Collection
{
    public function getItemClassName(): string
    {
        return Weight::class;
    }
}
