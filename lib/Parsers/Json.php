<?php

namespace MMSM\Lib\Parsers;

use Psr\Http\Message\ServerRequestInterface;

class Json implements BodyParserInterface
{

    /**
     * @inheritDoc
     */
    public function parseBody(ServerRequestInterface $request): ServerRequestInterface
    {
        $parsedBody = json_decode(
            $request->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        return $request->withParsedBody($parsedBody);
    }
}