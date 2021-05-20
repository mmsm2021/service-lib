<?php

namespace MMSM\Lib\Parsers;

class XmlBodyParser
{
    /**
     * @param string $body
     * @return mixed
     * @throws \JsonException
     */
    public function __invoke(string $body)
    {
        return json_decode(
            json_encode(
                simplexml_load_string(
                    $body,
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