<?php

declare(strict_types=1);

namespace App\RequestHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareChainHandler implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private array $chain = [];

    /**
     * MiddlewareChain constructor.
     * @param RequestHandlerInterface $defaultHandler
     */
    public function __construct(public RequestHandlerInterface $defaultHandler)
    {
    }

    /**
     * @param MiddlewareInterface $middleware
     */
    public function add(MiddlewareInterface $middleware): void
    {
        $this->chain[] = $middleware;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $chain = $this->defaultHandler;

        for ($i = count($this->chain) - 1; $i >= 0; --$i) {
            $chain = new MiddlewareHandler($this->chain[$i], $chain);
        }

        return $chain->handle($request);
    }
}