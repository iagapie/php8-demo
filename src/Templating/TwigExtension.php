<?php

declare(strict_types=1);

namespace App\Templating;

use IA\Route\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TwigExtension extends AbstractExtension
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'app';
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('url_for', [$this->urlGenerator, 'generate']),
            new TwigFunction('full_url_for', [$this->urlGenerator, 'absolute']),
        ];
    }
}