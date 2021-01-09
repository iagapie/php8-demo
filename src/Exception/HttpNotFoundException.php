<?php

declare(strict_types=1);

namespace App\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class HttpNotFoundException extends HttpException
{
    /**
     * HttpNotFoundException constructor.
     * @param ServerRequestInterface $request
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        ServerRequestInterface $request,
        string $message = 'Not Found',
        int $code = 404,
        ?Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);

        $this->title = '404 Not Found';
        $this->description = 'The requested resource could not be found. Please verify the URI and try again.';
    }
}