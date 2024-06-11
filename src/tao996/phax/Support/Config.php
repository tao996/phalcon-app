<?php

namespace Phax\Support;

class Config
{
    /**
     * @var \Phalcon\Config\Config
     */
    private static \Phalcon\Config\Config $config;

    public static function parse()
    {
        // 公共配置文件
        $pathDefaultConfig = PATH_ROOT . 'config/config.php';
        $data = include_once $pathDefaultConfig;

        $projectConfig = $data['app']['project'];
        $currentProject = $projectConfig['default'];
        // 根据当前域名来判断对应的是哪个站点
        $host = '';
        if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) { // 代理
            $host = str_replace('www.', '', $_SERVER['HTTP_X_FORWARDED_HOST']);
        } elseif (!empty($_SERVER['HTTP_HOST'])) { // docker.nginx 暴露 80 端口
            $host = preg_replace('|:\d+$|', '', $_SERVER['HTTP_HOST']);
        }

        foreach ($data['app']['project']['sites'] as $p => $hosts) {
            if (in_array($host, $hosts)) {
                $currentProject = $p;
                break;
            }
        }

// 应用配置
        if ($currentProject) {
            $pathProjectConfig = PATH_APP . 'Http/Projects/' . $currentProject . '/Config/' . ($projectConfig['prefix'] ? $currentProject . '.' : '') . 'config.php';
            if (file_exists($pathProjectConfig)) {
                $data = include_once $pathProjectConfig;
            }
        }
        if (empty($data)) {
            throw new \Exception('配置不能为空');
        }
        $data['app']['project']['default'] = $currentProject;
        $cc = new \Phalcon\Config\Config();
        $cc->merge($data);
        static::$config = $cc;
        return static::$config;
    }

    /**
     * 当前项目。
     * 1。应用的配置，如数据库；2。上传目录；3。缓存前缀
     * 如果为空，则会使用默认的全局配置信息
     */
    public static function currentProject($default = '')
    {
        return self::find('app.project.default', $default);
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