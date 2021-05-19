<?php

namespace MMSM\Lib\Parsers;

use Psr\Http\Message\StreamInterface;

class JsonBodyParser
{
    /**
     * @param StreamInterface $body
     * @return mixed
     * @throws \JsonException
     */
    public function __invoke(StreamInterface $body)
    {
        return json_decode(
            $body->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}