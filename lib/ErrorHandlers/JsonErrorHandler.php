<?php

namespace MMSM\Lib\ErrorHandlers;

use MMSM\Lib\Factories\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException;
use \Throwable;

class JsonErrorHandler implements LoggerAwareInterface, ErrorHandlerInterface
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
     * JsonErrorHandler constructor.
     * @param JsonResponseFactory $jsonResponseFactory
     */
    public function __construct(
        JsonResponseFactory $jsonResponseFactory
    ) {
        $this->jsonResponseFactory = $jsonResponseFactory;
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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
    ) : Response {
        $logArray = [];
        $responseArray = [
            'error' => true,
        ];

        $responseArray['message'] = [($throwable instanceof HttpException || $displayErrorDetails ?
            $throwable->getMessage() :
            'Internal Server Error'
        )];
        $logArray['message'] = $throwable->getMessage();
        $logArray['code'] = $throwable->getCode();
        $logArray['file'] = $throwable->getFile();
        $logArray['line'] = $throwable->getLine();

        if ($throwable instanceof HttpException || $displayErrorDetails) {
            $responseArray['code'] = $throwable->getCode();
        }

        if ($displayErrorDetails) {
            $responseArray['file'] = $throwable->getFile();
            $responseArray['line'] = $throwable->getLine();
        }

        if ($displayErrorDetails || $logErrorDetails) {
            foreach ($throwable->getTrace() as $key => $value) {
                $t = $value;
                if (isset($t['args'])) {
                    unset($t['args']);
                }
                if ($displayErrorDetails) {
                    $responseArray['trace'][] = $t;
                }
                if ($logErrorDetails) {
                    $logArray['trace'][] = $t;
                }
            }
        }
        if ($this->logger instanceof LoggerInterface && ($logErrors || !($throwable instanceof HttpException))) {
            try {
                $this->logger->error(json_encode(
                    $logArray,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                ));
            } catch (\JsonException $jsonException) {
                $this->logger->emergency(
                    'Failed to log error due to JsonException: "' . $jsonException->getMessage() . '".'
                );
            }
        }
        return $this->jsonResponseFactory->create(
            ($throwable instanceof HttpException ? $throwable->getCode() : 502),
            $responseArray
        );
    }
}