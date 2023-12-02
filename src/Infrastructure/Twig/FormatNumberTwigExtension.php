<?php

namespace App\Infrastructure\Twig;

final readonly class FormatNumberTwigExtension
{
    public static function doFormat(float $number, int $precision): string
    {
        return number_format(round($number, $precision), $precision, '.', ' ');
    }
}
