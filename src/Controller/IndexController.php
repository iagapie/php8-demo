<?php

declare(strict_types=1);

namespace App\Controller;

use App\Templating\EngineInterface;
use IA\Route\Attribute\Get;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class IndexController
{
    use ControllerTrait;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        private EngineInterface $engine,
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    #[Get('/', 'home')]
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $content = $this->engine->render('index/index.html.twig', [
            'uri' => $request->getUri(),
        ]);

        return $this->response(content: $content);
    }
}