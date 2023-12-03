<?php

namespace App\Infrastructure\Twig;

use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final readonly class TwigBuilder
{
    public function __construct(
        private FilesystemLoader $filesystemLoader
    ) {
    }

    public function build(): TwigEnvironment
    {
        $twig = new TwigEnvironment($this->filesystemLoader);
        $twig->addFunction(new TwigFunction('image64', [Base64TwigExtension::class, 'image']));
        $twig->addFunction(new TwigFunction('font64', [Base64TwigExtension::class, 'font']));
        $twig->addFunction(new TwigFunction('render', [new RenderTemplateTwigExtension($twig), 'render']));
        $twig->addFilter(new TwigFilter('repeat', [StrRepeatTwigExtension::class, 'doRepeat']));
        $twig->addFilter(new TwigFilter('ellipses', [StrEllipsisTwigExtension::class, 'doEllipses']));
        $twig->addFilter(new TwigFilter('formatNumber', [FormatNumberTwigExtension::class, 'doFormat']));

        return $twig;
    }
}
