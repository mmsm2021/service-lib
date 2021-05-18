<?php

namespace MMSM\Lib;

use MMSM\Lib\Parsers\BodyParserInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BodyParserMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var BodyParserInterface[]
     */
    private array $parsers;

    /**
     * BodyParserMiddleware constructor.
     * @param ContainerInterface $container
     * @param BodyParserInterface[] $parsers
     */
    public function __construct(ContainerInterface $container, array $parsers)
    {
        $this->parsers = $parsers;
        $this->container = $container;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            if ($request->hasHeader('Content-Type')) {
                $contentType = $request->getHeaderLine('Content-Type');

                if (!array_key_exists($contentType, $this->parsers)) {
                    return $this->handleUnknownContentType($handler, $request);
                }
                /** @var BodyParserInterface $parser */
                $parser = $this->container->get($this->parsers[$contentType]);
                $request = $parser->parseBody($request);
                return $handler->handle($request);
            } else {
                return $this->handleNoHeader($handler, $request);
            }
        } catch (\Throwable $throwable) {
            return $this->handleThrowable($throwable, $handler, $request);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->process($request, $handler);
    }

    /**
     * Implement this if you want to handle exceptions thrown while parsing the received body.
     * @param \Throwable $throwable
     * @param RequestHandlerInterface $handler
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleThrowable(
        \Throwable $throwable,
        RequestHandlerInterface $handler,
        ServerRequestInterface $request
    ): ResponseInterface {
        return $handler->handle($request);
    }

    /**
     * Implement this if you want to handle requests where the Content-Type header is missing.
     * @param RequestHandlerInterface $handler
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleNoHeader(
        RequestHandlerInterface $handler,
        ServerRequestInterface $request
    ): ResponseInterface {
        return $handler->handle($request);
    }

    /**
     * Implement this if you want to handle requests where the value of the Content-Type header does not match a known parser.
     * @param RequestHandlerInterface $handler
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleUnknownContentType(
        RequestHandlerInterface $handler,
        ServerRequestInterface $request
    ): ResponseInterface {
        return $handler->handle($request);
    }
}