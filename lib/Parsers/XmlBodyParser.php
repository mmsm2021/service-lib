<?php

namespace MMSM\Lib\Parsers;

use Psr\Http\Message\StreamInterface;

class XmlBodyParser
{
    /**
     * @param StreamInterface $body
     * @return mixed
     * @throws \JsonException
     */
    public function __invoke(StreamInterface $body)
    {
        return json_decode(
            json_encode(
                simplexml_load_string(
                    $body->getContents(),
                    "SimpleXMLElement",
                    LIBXML_NOCDATA
                ),
                JSON_THROW_ON_ERROR
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}