<?php

namespace MMSM\Lib\Validators;

interface ValidatorInterface
{
    /**
     * @param mixed $data
     * @return bool
     */
    public function validate($data): bool;

    /**
     * @param mixed $data
     * @return bool
     * @throws \Throwable
     */
    public function check($data): bool;
}