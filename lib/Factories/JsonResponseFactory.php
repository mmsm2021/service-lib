<?php

namespace MMSM\Lib\Factories;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class JsonResponseFactory
{
    /**
     * @var ResponseFactoryInterface
     */
    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param int $code
     * @param array $data
     * @return ResponseInterface
     */
    public function create(int $code, array $data = []): ResponseInterface
    {
        try {
            $response = $this->responseFactory->createResponse($code);
            if (!empty($data)) {
                $response->getBody()->write(json_encode(
                    $data,
                    JSON_PRETTY_PRINT |
                    JSON_THROW_ON_ERROR |
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                ));
                return $response->withHeader('Content-Type', 'application/json');
            }
            return $response;
        } catch (\Throwable $throwable) {
            return $this->responseFactory->createResponse(
                StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR,
                'Internal Server Error'
            );
        }
    }
}