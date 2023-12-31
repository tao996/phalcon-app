<?php

namespace Phax\Test;

class MockObject
{
    /**
     * @throws \ReflectionException
     */
    public static function getProperty($object, $property)
    {
        $reflectedClass = new \ReflectionClass($object);
        $reflection = $reflectedClass->getProperty($property);
        $reflection->setAccessible(true);
        return $reflection->getValue($object);
    }
}