<?php

namespace MMSM\Lib\Parsers;

class JsonBodyParser
{
    /**
     * @param string $body
     * @return mixed
     * @throws \JsonException
     */
    public function __invoke(string $body)
    {
        return json_decode(
            $body,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}