<?php

use MMSM\Lib\AuthorizationMiddleware;
use MMSM\Lib\ErrorHandlers\JsonErrorHandler;
use MMSM\Lib\ErrorHandlers\ValidationExceptionJsonHandler;
use MMSM\Lib\Exceptions\DefinitionException;
use MMSM\Lib\Parsers\JsonBodyParser;
use MMSM\Lib\Parsers\XmlBodyParser;
use MMSM\Lib\Validators\JWKValidator;
use MMSM\Lib\Validators\JWTValidator;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Respect\Validation\Exceptions\ValidationException;
use Slim\App;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\ErrorMiddleware;
use Slim\Psr7\Factory\ResponseFactory;
use function DI\create;
use function DI\env;
use function DI\get;
use function DI\string;

return [
    'user.valid.roleKeys' => [
        'user.roles.super',
        'user.roles.admin',
        'user.roles.employee',
        'user.roles.customer',
    ],
    'user.roles.super' => [
        'sa',
        'super admin',
    ],
    'user.roles.admin' => [
        'location administrator',
        'administrator',
        'admin',
    ],
    'user.roles.employee' => [
        'location employee',
        'employee',
    ],
    'user.roles.customer' => [
        'customer',
        'location customer',
    ],
    'custom.tokenClaim.namespace' => env('CUSTOM_TOKEN_NAMESPACE', 'https://frandine.randomphp.com'),
    'log.dir' => string('{root.dir}/var/log'),
    'environment' => env('ENV', 'development'),
    'log.errors' => env('LOG_ERRORS', false),
    'log.error.details' => env('LOG_ERROR_DETAILS', false),
    'auth.jwk_uri' => env('JWK_URI', false),
    'auth.allowedBearers' => [
        'Bearer'
    ],
    AuthorizationMiddleware::class => function(
        JWKValidator $JWKValidator,
        JWTValidator $JWTValidator,
        ContainerInterface $container
    ) : AuthorizationMiddleware {
        $authMiddleware = new AuthorizationMiddleware($JWKValidator, $JWTValidator);
        if (stristr($container->get('environment'), 'prod') !== false) {
            $authMiddleware->loadJWKs('/keys/auth0_jwks.json');
        } else {
            if (!is_string($container->get('auth.jwk_uri'))) {
                throw new DefinitionException('invalid type gotten from "auth.jwk_uri".');
            }
            $authMiddleware->loadJWKs($container->get('auth.jwk_uri'), false);
        }
        foreach ($container->get('auth.allowedBearers') as $bearer) {
            $authMiddleware->addAllowedBearer($bearer);
        }
        return $authMiddleware;
    },
    BodyParsingMiddleware::class => function(JsonBodyParser $jsonBodyParser, XmlBodyParser $xmlBodyParser) {
        return new BodyParsingMiddleware([
            'application/json' => $jsonBodyParser,
            'application/xml' => $xmlBodyParser,
        ]);
    },
    ResponseFactoryInterface::class => create(ResponseFactory::class),
    Logger::class => function(ContainerInterface $container) {
        if (!file_exists($container->get('log.dir'))) {
            mkdir($container->get('log.dir'), 0777, true);
        }
        return new Logger(
            'default',
            [
                new StreamHandler(
                    $container->get('log.dir') . '/debug.log',
                    Logger::DEBUG,
                    true,
                    0777,
                    true
                ),
                new StreamHandler(
                    $container->get('log.dir') . '/info.log',
                    Logger::INFO,
                    true,
                    0777,
                    true
                ),
                new StreamHandler(
                    $container->get('log.dir') . '/notice.log',
                    Logger::NOTICE,
                    true,
                    0777,
                    true
                ),
                new StreamHandler(
                    $container->get('log.dir') . '/warning.log',
                    Logger::WARNING,
                    true,
                    0777,
                    true
                ),
                new StreamHandler(
                    $container->get('log.dir') . '/error.log',
                    Logger::ERROR,
                    true,
                    0777,
                    true
                ),
                new StreamHandler(
                    $container->get('log.dir') . '/critical.log',
                    Logger::CRITICAL,
                    true,
                    0777,
                    true
                ),
                new StreamHandler(
                    $container->get('log.dir') . '/alert.log',
                    Logger::ALERT,
                    true,
                    0777,
                    true
                ),
                new StreamHandler(
                    $container->get('log.dir') . '/emergency.log',
                    Logger::EMERGENCY,
                    true,
                    0777,
                    true
                ),
            ],
            [],
            new DateTimeZone('UTC')
        );
    },
    LoggerInterface::class => get(Logger::class),
    ErrorMiddleware::class => function(
        ContainerInterface $container,
        JsonErrorHandler $jsonErrorHandler,
        ValidationExceptionJsonHandler $validationExceptionJsonHandler,
        LoggerInterface $logger,
        App $app
    ) : ErrorMiddleware {
        $env = $container->get('environment');
        $le = $container->get('log.errors');
        $led = $container->get('log.error.details');
        $isDevelopment = (stristr($env, 'dev') !== false);
        $errors = ($le === true || $le == 'true' || $isDevelopment);
        $errorDetails = ($led === true || $led == 'true' || $isDevelopment);
        $errorMiddleware = new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            $isDevelopment,
            $errors,
            $errorDetails,
            $logger
        );
        $errorMiddleware->setDefaultErrorHandler($jsonErrorHandler);
        $errorMiddleware->setErrorHandler(
            ValidationException::class,
            $validationExceptionJsonHandler,
            true
        );
        return $errorMiddleware;
    },
];