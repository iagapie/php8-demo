<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\HttpException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class ErrorMiddleware implements MiddlewareInterface
{
    /**
     * ErrorMiddleware constructor.
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface $streamFactory
     * @param LoggerInterface $logger
     * @param bool $debug
     */
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private LoggerInterface $logger,
        private bool $debug
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            return $this->handleException($request, $e);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param Throwable $exception
     * @return ResponseInterface
     */
    private function handleException(ServerRequestInterface $request, Throwable $exception): ResponseInterface
    {
        $this->logger->error('Error', ['exception' => $exception]);
        // TODO

        if ($this->debug) {
            return $this->responseFactory
                ->createResponse(400, 'Server Error')
                ->withBody($this->streamFactory->createStream($exception->getTraceAsString()));
        }

        if ($exception instanceof HttpException) {
            if ($exception->getCode() >= 400 && $exception->getCode() <= 511) {
                return $this->responseFactory->createResponse(
                    $exception->getCode(),
                    $exception->getMessage()
                )->withBody($this->streamFactory->createStream($exception->getDescription()));
            }
        }

        return $this->responseFactory->createResponse(500, 'Server Error');
    }
}