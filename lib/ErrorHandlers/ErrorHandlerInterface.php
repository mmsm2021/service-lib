<?php

namespace MMSM\Lib\ErrorHandlers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

interface ErrorHandlerInterface
{
    /**
     * @param Request $request
     * @param Throwable $throwable
     * @param bool $displayErrorDetails
     * @param bool $logErrors
     * @param bool $logErrorDetails
     * @return Response
     */
    public function __invoke(
        Request $request,
        Throwable $throwable,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) : Response;
}