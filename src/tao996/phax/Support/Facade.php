<?php

namespace Phax\Support;

use http\Exception\RuntimeException;

abstract class Facade
{
    private static $resolvedInstances = [];

    protected static function getFacadeName(): string
    {
        throw new \RuntimeException('you should implement Facade name');
    }

    protected static function getFacadeObject()
    {
        throw new \RuntimeException('you should implement Facade object');
    }

    private static function resolveInstance($name)
    {
        if (!isset(static::$resolvedInstances[$name])) {
            static::$resolvedInstances[$name] = static::getFacadeObject();
        }
        return static::$resolvedInstances[$name];
    }

    private static function getFacadeRoot()
    {
        return static::resolveInstance(static::getFacadeName());
    }

    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeRoot();
        if (!$instance) {
            throw new RuntimeException('a Facade is not set');
        }
        return $instance->$method(...$args);
    }
}