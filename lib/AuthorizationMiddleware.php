<?php

namespace MMSM\Lib;

use MMSM\Lib\Exceptions\JWKKeyFileException;
use MMSM\Lib\Validators\JWKValidator;
use MMSM\Lib\Validators\JWTValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Respect\Validation\Exceptions\ValidationException;
use SimpleJWT\InvalidTokenException;
use SimpleJWT\JWT;
use SimpleJWT\Keys\KeySet;
use SimpleJWT\Keys\RSAKey;
use Respect\Validation\Validator as V;
use Slim\Exception\HttpException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpUnauthorizedException;

class AuthorizationMiddleware implements MiddlewareInterface
{
    public const HEADER = 'Authorization';
    public const EXPECTED_ALG = 'RS256';

    /**
     * @var JWKValidator
     */
    private JWKValidator $JWKValidator;

    /**
     * @var JWTValidator
     */
    private JWTValidator $JWTValidator;

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
     * @param JWTValidator $JWTValidator
     */
    public function __construct(JWKValidator $JWKValidator, JWTValidator $JWTValidator)
    {
        $this->JWKValidator = $JWKValidator;
        $this->JWTValidator = $JWTValidator;
    }

    /**
     * @param string $path
     * @param bool $local
     * @throws JWKKeyFileException
     * @throws \Throwable
     */
    public function loadJWKs(string $path, bool $local = true)
    {
        try {
            $this->keySet = null;
            if ($local && !file_exists($path)) {
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
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            if (!$request->hasHeader('Authorization')) {
                return $handler->handle($request->withAttribute(
                    'token',
                    new HttpUnauthorizedException($request, 'Missing "Authorization" header.')
                ));
            }
            $header = $this->getHeader($request);
            if (count($header) != 2) {
                return $handler->handle($request->withAttribute(
                    'token',
                    new HttpUnauthorizedException($request, 'Invalid "Authorization" header.')
                ));
            }
            $bearer = $header[0];

            if (!$this->isValidBearer($bearer)) {
                return $handler->handle($request->withAttribute(
                    'token',
                    new HttpUnauthorizedException($request, 'Invalid Bearer in "Authorization" header.')
                ));
            }
            $token = $header[1];
            if (!$this->JWTValidator->validate($token)) {
                return $handler->handle($request->withAttribute(
                    'token',
                    new HttpUnauthorizedException($request, 'Invalid Token in "Authorization" header.')
                ));
            }

            if (!($this->keySet instanceof KeySet)) {
                return $handler->handle($request->withAttribute(
                    'token',
                    new HttpInternalServerErrorException($request, 'Failed to access keyset.')
                ));
            }
            return $handler->handle($request->withAttribute(
                'token',
                JWT::decode($token, $this->keySet, self::EXPECTED_ALG)
            ));
        } catch (InvalidTokenException $invalidTokenException) {
            return $handler->handle($request->withAttribute(
                'token',
                new HttpUnauthorizedException($request, 'JWT verification failed.', $invalidTokenException)
            ));
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
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function getHeader(ServerRequestInterface $request): array
    {
        $header = $request->getHeader(self::HEADER);
        if (is_array($header)) {
            $header = $header[array_keys($header)[0]];
        }
        $header = preg_replace('/\s+/', ' ', $header);
        return explode(' ', $header);
    }

    /**
     * @param $bearer
     * @return bool
     */
    protected function isValidBearer($bearer): bool
    {
        return in_array($bearer, $this->bearers);
    }
}