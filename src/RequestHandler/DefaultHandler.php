<?php

declare(strict_types=1);

namespace App\RequestHandler;

use App\Exception\HttpNotFoundException;
use IA\Route\Resolver\Result;
use IA\Route\RouteInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

final class DefaultHandler implements RequestHandlerInterface
{
    /**
     * DefaultHandler constructor.
     * @param ContainerInterface $container
     */
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ReflectionException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Result $result */
        $result = $request->getAttribute(Result::class);

        /** @var RouteInterface $route */
        $route = $request->getAttribute(RouteInterface::class);

        [$serviceId, $method] = \explode('::', $route->getHandler());

        $reflectionMethod = new ReflectionMethod($serviceId, $method);

        $chain = new MiddlewareChainHandler(
            new class ($this, $reflectionMethod, $serviceId, $result->getArguments()) implements RequestHandlerInterface {
                public function __construct(
                    private DefaultHandler $defaultHandler,
                    private ReflectionMethod $reflectionMethod,
                    private string $serviceId,
                    private array $arguments
                ) {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->defaultHandler->invoke($request, $this->reflectionMethod, $this->serviceId, $this->arguments);
                }
            }
        );

        foreach ($route->getMiddlewares() as $middleware) {
            $chain->add($this->container->get($middleware));
        }

        return $chain->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ReflectionMethod $reflectionMethod
     * @param string $serviceId
     * @param array $arguments
     * @return ResponseInterface
     * @throws ReflectionException
     */
    public function invoke(
        ServerRequestInterface $request,
        ReflectionMethod $reflectionMethod,
        string $serviceId,
        array $arguments
    ): ResponseInterface {
        if (!$this->container->has($serviceId)) {
            throw new HttpNotFoundException($request);
        }

        $parameters = [];

        foreach ($reflectionMethod->getParameters() as $parameter) {
            if ($parameter->hasType() && $parameter->getType() instanceof ReflectionNamedType) {
                if (\is_a($parameter->getType()->getName(), ServerRequestInterface::class, true)) {
                    $parameters[$parameter->getName()] = $request;
                    continue;
                }

                if ($this->container->has($parameter->getType()->getName())) {
                    $parameters[$parameter->getName()] = $this->container->get($parameter->getType()->getName());
                    continue;
                }
            }

            if (\array_key_exists($parameter->getName(), $arguments)) {
                $parameters[$parameter->getName()] = $arguments[$parameter->getName()];
                continue;
            }

            $parameters[$parameter->getName()] = null;
        }

        $service = $this->container->get($serviceId);

        return $reflectionMethod->invokeArgs($service, $parameters);
    }
}