<?php

namespace App\Infrastructure\Twig;

use Twig\Environment;

final readonly class RenderTemplateTwigExtension
{
    public function __construct(
        private Environment $twig
    ) {
    }

    public function render(string $template, array $context = []): string
    {
        return $this->twig->render($template, $context);
    }
}
