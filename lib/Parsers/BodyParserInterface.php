<?php

namespace MMSM\Lib\Parsers;

use Psr\Http\Message\ServerRequestInterface;

interface BodyParserInterface
{
    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     * @throws \Throwable
     */
    public function parseBody(ServerRequestInterface $request): ServerRequestInterface;
}