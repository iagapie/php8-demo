<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\HttpMethodNotAllowedException;
use App\Exception\HttpNotFoundException;
use IA\Route\Exception\RouteException;
use IA\Route\Resolver\Result;
use IA\Route\Resolver\RouteResolverInterface;
use IA\Route\RouteInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final class RouteMiddleware implements MiddlewareInterface
{
    public function __construct(private RouteResolverInterface $routeResolver, private LoggerInterface $logger)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Result $result */
        $result = $request->getAttribute(Result::class);

        $this->logger->debug(
            'RouteMiddleware - result {status} for {uri}',
            ['status' => $result->getStatus(), 'uri' => $request->getUri()->getPath()]
        );

        switch ($result->getStatus()) {
            case Result::NOT_FOUND:
                throw new HttpNotFoundException($request);
            case Result::METHOD_NOT_ALLOWED:
                throw new HttpMethodNotAllowedException($request);
        }

        try {
            $route = $this->routeResolver->resolve($result->getIdentifier());
        } catch (RouteException $e) {
            throw new HttpNotFoundException($request, previous: $e);
        }

        $request = $request->withAttribute(RouteInterface::class, $route);

        return $handler->handle($request);
    }
}