<?php

namespace MMSM\Lib;

use MMSM\Lib\Factories\JsonResponseFactory;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Middleware\BodyParsingMiddleware as Base;
use Throwable;

class BodyParsingMiddleware extends Base
{
    /**
     * @var JsonResponseFactory
     */
    private JsonResponseFactory $jsonResponseFactory;

    /**
     * @inheritDoc
     */
    public function __construct(
        JsonResponseFactory $jsonResponseFactory,
        array $bodyParsers = []
    ) {
        parent::__construct($bodyParsers);
        $this->jsonResponseFactory = $jsonResponseFactory;
    }

    /**
     * @inheritDoc
     */
    protected function parseBody(ServerRequestInterface $request)
    {
        try {
            return parent::parseBody($request);
        } catch (Throwable $exception) {
            $this->jsonResponseFactory->create(400, [
                'error' => true,
                'message' => ['Failed parsing request body.']
            ]);
        }
    }
}