<?php

declare(strict_types=1);

namespace App\Templating;

interface EngineInterface
{
    /**
     * @param string $template
     * @param array $parameters
     * @return string
     */
    public function render(string $template, array $parameters = []): string;

    /**
     * @param string $template
     * @param string $block
     * @param array $parameters
     * @return string
     */
    public function renderBlock(string $template, string $block, array $parameters = []): string;
}