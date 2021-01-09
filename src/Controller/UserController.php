<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\JsonContentTypeMiddleware;
use App\Templating\EngineInterface;
use IA\Route\Attribute\Get;
use IA\Route\Attribute\Prefix;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

#[Prefix('/user')]
class UserController
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

    #[Get('/{id:[0-9]+}', 'user_view')]
    public function view(ServerRequestInterface $request, int $id): ResponseInterface
    {
        $content = $this->engine->render('user/view.html.twig', [
            'id' => $id,
            'uri' => $request->getUri(),
        ]);

        return $this->response(content: $content);
    }

    #[Get('/{name}', 'user_json_view', JsonContentTypeMiddleware::class)]
    public function jsonView(ServerRequestInterface $request, string $name): ResponseInterface
    {
        $data = [
            'uri_path' => $request->getUri()->getPath(),
            'name' => $name,
        ];

        return $this->json($data);
    }
}