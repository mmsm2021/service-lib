<?php

namespace MMSM\Lib;

use MMSM\Lib\Exceptions\JwkException;
use Psr\Container\ContainerInterface;
use SimpleJWT\JWT;
use SimpleJWT\Keys\Key;

class JwtHandler
{
    public const ALG = 'HS256';

    /**
     * @var InternalKeySet
     */
    private InternalKeySet $internalKeySet;

    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * JwtFactory constructor.
     * @param InternalKeySet $internalKeySet
     * @param ContainerInterface $container
     */
    public function __construct(
        InternalKeySet $internalKeySet,
        ContainerInterface $container
    ) {
        $this->internalKeySet = $internalKeySet;
        $this->container = $container;
    }

    /**
     * @return string
     * @throws JwkException
     */
    protected function getNewestKeyId(): string
    {
        $newest = null;
        $id = null;
        foreach($this->internalKeySet->getKeys() as $key) {
            /** @var Key $key */
            $data = $key->getKeyData();
            if (isset($data['exp']) &&
                is_numeric($data['exp']) &&
                ($newest === null || $data['exp'] > $newest)
            ) {
                $newest = $data['exp'];
                $id = $key->getKeyId();
            }
        }
        if (!is_string($id)) {
            throw new JwkException('Failed to find newest kid.');
        }
        return $id;
    }

    /**
     * @param array $data
     * @param int|null $exp
     * @return string
     * @throws JwkException
     */
    public function create(array $data, ?int $exp = null): string
    {
        $jwt = new JWT([
            'alg' => self::ALG,
            'typ' => 'JWT',
        ], array_merge($data, [
            'exp' => ($exp === null ? strtotime('+24 hours') : $exp),
            'iss' => $this->container->get('auth.jwt.issuer'),
        ]));
        return $jwt->encode($this->internalKeySet, $this->getNewestKeyId());
    }

    /**
     * @param string $jwt
     * @return JWT
     */
    public function decode(string $jwt): JWT
    {
        return JWT::decode($jwt, $this->internalKeySet, self::ALG);
    }
}