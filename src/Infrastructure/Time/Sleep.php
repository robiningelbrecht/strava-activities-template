<?php

namespace App\Infrastructure\Time;

interface Sleep
{
    public function sweetDreams(int $durationInSeconds): void;
}
