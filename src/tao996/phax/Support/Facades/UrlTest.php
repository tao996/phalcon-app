<?php

namespace Phax\Support\Facades;

use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    public function testCreatePath()
    {

        $this->assertEquals('/auth', Url::createPath('/auth'));
        $this->assertEquals('/auth', Url::createPath('auth'));

        $path = Url::createPath('auth', [], 'm');
        $this->assertEquals('/m/auth', $path);

        $path = Url::createPath('auth', [], 'm', 'api');
        $this->assertEquals('/api/m/auth', $path);

        $path = Url::createPath('auth', [], 'm', 'api', 'en');
        $this->assertEquals('/en/api/m/auth', $path);

        $path = Url::createPath('/auth', [], false, 'api', 'en', 'https://test.com');
        $this->assertEquals('https://test.com/en/api/auth', $path);

    }
}