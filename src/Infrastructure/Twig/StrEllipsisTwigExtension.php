<?php

namespace App\Infrastructure\Twig;

final readonly class StrEllipsisTwigExtension
{
    public static function doEllipses(string $string, int $maxLength): string
    {
        if (strlen($string) <= $maxLength) {
            return $string;
        }

        return mb_substr($string, 0, $maxLength - 3, 'UTF-8').'...';
    }
}
