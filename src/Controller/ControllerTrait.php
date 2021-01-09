<?php

declare(strict_types=1);

namespace App\Controller;

use ArrayObject;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

use function json_encode;

trait ControllerTrait
{
    protected int $jsonOptions = JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PRETTY_PRINT;

    protected ResponseFactoryInterface $responseFactory;
    protected StreamFactoryInterface $streamFactory;

    /**
     * @param string $url
     * @param int $code
     * @return ResponseInterface
     */
    protected function redirect(string $url, int $code = 302): ResponseInterface
    {
        return $this->response($code)->withHeader('Location', $url);
    }

    /**
     * @param int $code
     * @param string $reasonPhrase
     * @param string $content
     * @return ResponseInterface
     */
    protected function response(int $code = 200, string $reasonPhrase = '', string $content = ''): ResponseInterface
    {
        return $this->responseFactory->createResponse($code, $reasonPhrase)->withBody($this->stream($content));
    }

    /**
     * @param string $content
     * @return StreamInterface
     */
    protected function stream(string $content = ''): StreamInterface
    {
        return $this->streamFactory->createStream($content);
    }

    /**
     * @param mixed|null $data
     * @param int $code
     * @param bool $json
     * @return ResponseInterface
     */
    protected function json(mixed $data = null, int $code = 200, bool $json = false): ResponseInterface
    {
        if (null === $data) {
            $data = new ArrayObject();
            $json = false;
        }

        if (!$json) {
            $data = json_encode($data, $this->jsonOptions);
        }

        return $this->response($code, content: $data);
    }
}