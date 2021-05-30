<?php

namespace MMSM\Lib;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SimpleJWT\JWT;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpUnauthorizedException;
use Throwable;
use Psr\Http\Message\ServerRequestInterface as Request;

class Authorizer
{

    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * Authorizer constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @param string $roleKey
     * @return bool
     * @throws Throwable
     */
    public function authorizeToRole(Request $request, string $roleKey): bool
    {
        return $this->authorizeToRoles($request, [$roleKey]);
    }

    /**
     * @param Request $request
     * @param string[] $roleKeys
     * @return bool
     * @throws Throwable
     */
    public function authorizeToRoles(Request $request, array $roleKeys): bool
    {
        $token = $this->readTokenFromRequest($request);
        if (!($token instanceof JWT)) {
            throw $token;
        }
        if (!$this->hasRoles($request, $roleKeys, true)) {
            throw new HttpUnauthorizedException($request, 'Not authorized.');
        }
        return true;
    }

    /**
     * @param Request $request
     * @param string $roleKey
     * @param bool $failOnNoToken
     * @return bool
     * @throws Throwable
     */
    public function hasRole(
        Request $request,
        string $roleKey,
        bool $failOnNoToken = true
    ): bool {
        return $this->hasRoles($request, [$roleKey], $failOnNoToken);
    }

    /**
     * @param Request $request
     * @param string[] $roleKeys
     * @param bool $failOnNoToken
     * @return bool
     * @throws Throwable
     */
    public function hasRoles(
        Request $request,
        array $roleKeys,
        bool $failOnNoToken = true
    ): bool {
        if (!$this->isValidRoleKeys($roleKeys)) {
            throw new HttpInternalServerErrorException($request, 'Invalid role key.');
        }
        $token = $this->readTokenFromRequest($request);
        if (!($token instanceof JWT)) {
            if ($failOnNoToken) {
                throw $token;
            }
            return false;
        }
        $internalRoles = $this->getInternalRoles($roleKeys);
        return $this->hasInternalRoles($token, $internalRoles);
    }

    /**
     * @param string[] $roles
     * @return string[]
     */
    public function getInternalRoles(array $roles): array
    {
        try {
            $internalRoles = [];
            foreach ($roles as $role) {
                $internalRoles = array_merge($internalRoles, $this->container->get($role));
            }
            return $internalRoles;
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $exception) {
            return [];
        }
    }

    /**
     * @param Request $request
     * @param bool $emptyOnFailure
     * @return array
     * @throws HttpUnauthorizedException
     * @throws Throwable
     */
    public function getUserMetadata(Request $request, bool $emptyOnFailure = false): array
    {
        $token = $this->readTokenFromRequest($request);
        if (!($token instanceof JWT)) {
            if ($emptyOnFailure) {
                return [];
            }
            throw $token;
        }
        $claim = $this->readNamespacedClaim($token, 'user_metadata');
        if (is_array($claim)) {
            return $claim;
        }
        if ($emptyOnFailure) {
            return [];
        }
        throw new HttpUnauthorizedException(
            $request,
            'Missing "user_metadata" claim.'
        );
    }

    /**
     * @param Request $request
     * @param bool $emptyOnFailure
     * @return array
     * @throws HttpUnauthorizedException
     * @throws Throwable
     */
    public function getAppMetadata(Request $request, bool $emptyOnFailure = false): array
    {
        $token = $this->readTokenFromRequest($request);
        if (!($token instanceof JWT)) {
            if ($emptyOnFailure) {
                return [];
            }
            throw $token;
        }
        $claim = $this->readNamespacedClaim($token, 'app_metadata');
        if (is_array($claim)) {
            return $claim;
        }
        if ($emptyOnFailure) {
            return [];
        }
        throw new HttpUnauthorizedException(
            $request,
            'Missing "app_metadata" claim.'
        );
    }

    /**
     * @param string[] $keys
     * @return bool
     */
    protected function isValidRoleKeys(array $keys): bool
    {
        $validRoleKeys = $this->container->get('user.valid.roleKeys');
        foreach ($keys as $key) {
            if (!in_array($key, $validRoleKeys)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param JWT $token
     * @param string[] $roles
     * @return bool
     */
    protected function hasInternalRoles(JWT $token, array $roles): bool
    {
        $claim = $this->readNamespacedClaim($token, 'roles');
        if (is_array($claim) && !empty($claim)) {
            foreach($claim as $roleOnClaim) {
                if (in_array(strtolower($roleOnClaim), $roles)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param JWT $token
     * @param string $name
     * @return mixed
     */
    protected function readNamespacedClaim(JWT $token, string $name)
    {
        $namespace = $this->container->get('custom.tokenClaim.namespace');
        $claimKey = $namespace . '/' . $name;
        return $token->getClaim($claimKey);
    }

    /**
     * @param Request $request
     * @return JWT|Throwable
     */
    protected function readTokenFromRequest(Request $request)
    {
        $token = $request->getAttribute('token', null);
        if ($token instanceof Throwable) {
            return $token;
        }
        if (!($token instanceof JWT)) {
            return new HttpUnauthorizedException(
                $request,
                'Invalid Token.'
            );
        }
        return $token;
    }

}