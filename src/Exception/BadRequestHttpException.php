<?php

declare(strict_types=1);

namespace App\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class BadRequestHttpException extends HttpException
{
    /**
     * BadRequestHttpException constructor.
     * @param ServerRequestInterface $request
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        ServerRequestInterface $request,
        string $message,
        int $code = 400,
        ?Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);

        $this->title = '400 Bad Request';
        $this->description = 'The request is badly performed.';
    }
}