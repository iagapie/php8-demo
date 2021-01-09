<?php

declare(strict_types=1);

namespace App\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class HttpForbiddenException extends HttpException
{
    /**
     * HttpForbiddenException constructor.
     * @param ServerRequestInterface $request
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        ServerRequestInterface $request,
        string $message = 'Forbidden',
        int $code = 403,
        ?Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);

        $this->title = '403 Forbidden';
        $this->description = 'You are not permitted to perform the requested operation.';
    }
}