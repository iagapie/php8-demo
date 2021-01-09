<?php

declare(strict_types=1);

namespace App\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class HttpMethodNotAllowedException extends HttpException
{
    /**
     * HttpMethodNotAllowedException constructor.
     * @param ServerRequestInterface $request
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        ServerRequestInterface $request,
        string $message = 'Method Not Allowed',
        int $code = 405,
        ?Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);

        $this->title = '405 Method Not Allowed';
        $this->description = 'The request method is not supported for the requested resource.';
    }
}