<?php

namespace MMSM\Lib\ErrorHandlers;

use MMSM\Lib\Factories\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Exceptions\ValidationException;
use Throwable;

class ValidationExceptionJsonHandler implements LoggerAwareInterface, ErrorHandlerInterface
{
    /**
     * @var JsonResponseFactory
     */
    private JsonResponseFactory $jsonResponseFactory;

    /**
     * @var LoggerInterface|null
     */
    private ?LoggerInterface $logger = null;

    /**
     * ValidationExceptionJsonHandler constructor.
     * @param JsonResponseFactory $jsonResponseFactory
     */
    public function __construct(JsonResponseFactory $jsonResponseFactory)
    {
        $this->jsonResponseFactory = $jsonResponseFactory;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(
        Request $request,
        Throwable $throwable,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) : Response {
        if (!($throwable instanceof ValidationException)) {
            if ($this->logger instanceof LoggerInterface && $logErrors) {
                $this->logger->critical('"' . static::class . '" can only handle "' .
                    ValidationException::class . '" not "' .
                    get_class($throwable) . '"');
            }
            return $this->jsonResponseFactory->create(500, [
                'error' => true,
                'message' => [($displayErrorDetails ?
                    '"' . static::class . '" can only handle "' . ValidationException::class . '" not "' .
                    get_class($throwable) . '"' :
                    'Internal Server Error'
                )]
            ]);
        }
        return $this->jsonResponseFactory->create(
            400, [
                'error' => true,
                'message' => ($throwable instanceof NestedValidationException ?
                    array_values($throwable->getMessages()) :
                    [$throwable->getMessage()]
                ),
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}