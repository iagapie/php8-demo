<?php

declare(strict_types=1);

namespace App\Templating;

use Twig\Environment;

final class TwigEngine implements EngineInterface
{
    /**
     * TwigEngine constructor.
     * @param Environment $environment
     */
    public function __construct(private Environment $environment)
    {
    }

    /**
     * @param string $template
     * @param array $parameters
     * @return string
     */
    public function render(string $template, array $parameters = []): string
    {
        return $this->environment->render($template, $parameters);
    }

    /**
     * @param string $template
     * @param string $block
     * @param array $parameters
     * @return string
     */
    public function renderBlock(string $template, string $block, array $parameters = []): string
    {
        return $this->environment
            ->resolveTemplate($template)
            ->renderBlock($block, $parameters);
    }
}