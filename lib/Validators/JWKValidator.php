<?php

namespace MMSM\Lib\Validators;

use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as V;

class JWKValidator implements ValidatorInterface
{

    /**
     * @inheritDoc
     */
    public function validate($data): bool
    {
        try {
            return $this->check($data);
        } catch (ValidationException $exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function check($data): bool
    {
        v::arrayType()
            ->key('alg', V::stringType()->notEmpty(), true)
            ->key('kty', V::stringType()->notEmpty(), true)
            ->key('use', V::stringType()->notEmpty(), true)
            ->key('n', V::stringType()->notEmpty(), true)
            ->key('e', V::stringType()->notEmpty(), true)
            ->key('kid', V::stringType()->notEmpty(), true)
            ->key('x5t', V::stringType()->notEmpty(), true)
            ->key('x5c', V::arrayType()->each(
                v::stringType()->notEmpty()
            ), true)->check($data);
        return true;
    }
}