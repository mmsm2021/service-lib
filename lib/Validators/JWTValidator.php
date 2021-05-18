<?php

namespace MMSM\Lib\Validators;

use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as V;

class JWTValidator implements ValidatorInterface
{

    /**
     * @inheritDoc
     */
    public function validate($data): bool
    {
        try {
            return $this->check($data);
        } catch (ValidationException $validationException) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function check($data): bool
    {
        v::stringType()
            ->notEmpty()
            ->contains('.')
            ->regex('/([^.]+)\\.([^.]+)\\.([^.]+)/')
            ->check($data);
        return true;
    }
}