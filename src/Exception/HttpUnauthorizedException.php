<?php

declare(strict_types=1);

namespace App\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class HttpUnauthorizedException extends HttpException
{
    /**
     * HttpUnauthorizedException constructor.
     * @param ServerRequestInterface $request
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        ServerRequestInterface $request,
        string $message = 'Unauthorized',
        int $code = 401,
        ?Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);

        $this->title = '401 Unauthorized';
        $this->description = 'The request requires valid user authentication.';
    }
}