<?php

namespace MMSM\Lib;

use MMSM\Lib\Exceptions\JWKKeyFileException;
use MMSM\Lib\Validators\JWKValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Respect\Validation\Exceptions\ValidationException;
use SimpleJWT\Keys\KeySet;
use SimpleJWT\Keys\RSAKey;
use Slim\Exception\HttpBadRequestException;
use Respect\Validation\Validator as V;
use Slim\Exception\HttpException;

class AuthorizationMiddleware implements MiddlewareInterface
{
    public const HEADER = 'Authorization';

    /**
     * @var JWKValidator
     */
    private JWKValidator $JWKValidator;

    /**
     * @var KeySet|null
     */
    private ?KeySet $keySet = null;

    /**
     * @var string[]
     */
    private array $bearers = [];

    /**
     * AuthorizationMiddleware constructor.
     * @param JWKValidator $JWKValidator
     */
    public function __construct(JWKValidator $JWKValidator)
    {
        $this->JWKValidator = $JWKValidator;
    }

    /**
     * @param string $path
     * @throws JWKKeyFileException
     */
    public function loadJWKs(string $path)
    {
        try {
            $this->keySet = null;
            if (!file_exists($path)) {
                throw new JWKKeyFileException('Failed to find JWK Key file: "' . $path . '".');
            }
            $content = file_get_contents($path);
            if ($content === false) {
                throw new JWKKeyFileException('Failed to read JWK key file: "' . $path . '".');
            }
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new JWKKeyFileException('Failed to parse JSON in JWK key file: "' . $path . '".');
            }
            V::arrayType()->notEmpty()->key('keys', V::arrayType()->notEmpty())->check($data);
            $keySet = new KeySet();
            foreach ($data['keys'] as $key) {
                $this->JWKValidator->check($key);
                $keySet->add(new RSAKey($key, 'php'));
            }
            $this->keySet = $keySet;
        } catch (ValidationException $exception) {
            throw new JWKKeyFileException('Content Validation failed.', $exception->getCode(), $exception);
        }
    }

    /**
     * @param string $bearer
     */
    public function addAllowedBearer(string $bearer)
    {
        $this->bearers[] = $bearer;
    }

    /**
     * @inheritDoc
     * @throws HttpException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$request->hasHeader('Authorization')) {
            throw new HttpBadRequestException($request, 'Missing "Authorization" header.');
        }
        $header = $request->getHeader(self::HEADER);

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


}