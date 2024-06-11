<?php

namespace Phax\Utils;

use PHPUnit\Framework\TestCase;

class MyUrlTest extends TestCase
{
    public function testCreatePath()
    {

        $this->assertEquals('/auth', MyUrl::createPath('/auth'));
        $this->assertEquals('/auth', MyUrl::createPath('auth'));

        $path = MyUrl::createPath('auth', [], 'm');
        $this->assertEquals('/m/auth', $path);

        $path = MyUrl::createPath('auth', [], 'm', 'api');
        $this->assertEquals('/api/m/auth', $path);

        $path = MyUrl::createPath('auth', [], 'm', 'api', 'en');
        $this->assertEquals('/en/api/m/auth', $path);

        $path = MyUrl::createPath('/auth', [], false, 'api', 'en', 'https://test.com');
        $this->assertEquals('https://test.com/en/api/auth', $path);

    }
}