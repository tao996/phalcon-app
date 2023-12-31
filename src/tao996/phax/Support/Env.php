<?php

namespace Phax\Support;

use Symfony\Component\Dotenv\Dotenv;

class Env
{
    public static function parse($pathEnv = '')
    {
        if ($pathEnv == '') {
            $pathEnv = PATH_ROOT . '.env';
        }
        if (file_exists($pathEnv)) {
            $dotenv = new Dotenv();
            $dotenv->overload($pathEnv);
        }
    }

    public static function find($name, $value = null)
    {
        return $_ENV[$name] ?? $value;
    }
}