<?php

namespace App\Infrastructure\Twig;

final readonly class StrRepeatTwigExtension
{
    public static function doRepeat(string $char, int $times): string
    {
        return str_repeat($char, $times);
    }
}
