<?php

namespace MMSM\Lib;

use DI\Container as Base;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class Container extends Base
{
    /**
     * @inheritDoc
     */
    public function get($name)
    {
        $wasResolved = false;
        if (isset($this->resolvedEntries[$name]) || array_key_exists($name, $this->resolvedEntries)) {
            $wasResolved = true;
        }
        $obj = parent::get($name);
        if (!$wasResolved && $obj instanceof LoggerAwareInterface) {
            $obj->setLogger(parent::get(LoggerInterface::class));
        }
        return $obj;
    }
}