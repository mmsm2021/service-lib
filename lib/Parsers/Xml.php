<?php

namespace MMSM\Lib\Parsers;

use Psr\Http\Message\ServerRequestInterface;

class Xml implements BodyParserInterface
{

    /**
     * @inheritDoc
     */
    public function parseBody(ServerRequestInterface $request): ServerRequestInterface
    {
        return $request->withParsedBody(json_decode(
            json_encode(
                simplexml_load_string(
                    $request->getBody()->getContents(),
                    "SimpleXMLElement",
                    LIBXML_NOCDATA
                ),
                JSON_THROW_ON_ERROR
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        ));
    }
}