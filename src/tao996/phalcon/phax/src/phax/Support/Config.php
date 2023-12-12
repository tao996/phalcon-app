<?php

namespace Phax\Support;

class Config
{
    /**
     * @var \Phalcon\Config\Config
     */
    private static $config;

    public static function parse($pathConfig = '')
    {
        $has = false;
        $cc = new \Phalcon\Config\Config();
        if (file_exists(PATH_ROOT . 'config/config.php')) {
            $cc->merge(include_once PATH_ROOT . 'config/config.php');
            $has = true;
        }
        $project = $cc->path('app.project', env('APP_PROJECT', ''));
        $projectFile = PATH_ROOT . 'config/' . $project . '.php';
        if (file_exists($projectFile)) {
            $cc->merge(include_once $projectFile);
            $has = true;
        }
        if (!$has){
            throw new \Exception('could not load any config');
        }
        static::$config = $cc;
        return static::$config;
    }

    public static function merge(\Phalcon\Config\Config $config)
    {
        static::$config->merge($config);
    }

    /**
     * 查询配置信息
     * @param $path string 路径 app 或者 app.name
     * @param $default
     * @return mixed
     */
    public static function find(string $path, $default)
    {
        return static::$config->path($path, $default);
    }
}