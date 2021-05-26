<?php

namespace MMSM\Lib;

use Psr\Container\ContainerInterface;
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
        $roles = [];
        $validRoleKeys = $this->container->get('user.valid.roleKeys');
        foreach ($roleKeys as $key) {
            if (!in_array($key, $validRoleKeys)) {
                throw new HttpInternalServerErrorException(
                    $request,
                    '"' . $key . '" is not a known valid role key.'
                );
            }
            $roles = array_merge($roles, $this->container->get($key));
        }
        return $this->authorizeToInternalRoles($token, $roles, $request);
    }

    /**
     * @param JWT $token
     * @param array $roles
     * @param Request $request
     * @return bool
     * @throws HttpUnauthorizedException
     */
    protected function authorizeToInternalRoles(JWT $token, array $roles, Request $request): bool
    {
        $namespace = $this->container->get('custom.tokenClaim.namespace');
        $claimKey = $namespace . '/roles';
        $claim = $token->getClaim($claimKey);
        if (!is_array($claim) || empty($claim)) {
            throw new HttpUnauthorizedException(
                $request,
                'Missing or Empty Claim.'
            );
        }
        foreach($claim as $roleOnClaim) {
            if (in_array($roleOnClaim, $roles)) {
                return true;
            }
        }
        throw new HttpUnauthorizedException(
            $request,
            'Missing Authorized role.'
        );
    }

    /**
     * @param Request $request
     * @return JWT
     * @throws Throwable
     */
    protected function readTokenFromRequest(Request $request): JWT
    {
        $token = $request->getAttribute('token', null);
        if ($token instanceof \Throwable) {
            throw $token;
        }
        if (!($token instanceof JWT)) {
            throw new HttpUnauthorizedException(
                $request,
                'Invalid Token.'
            );
        }
        return $token;
    }

}