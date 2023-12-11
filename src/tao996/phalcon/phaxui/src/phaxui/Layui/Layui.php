<?php

namespace Phaxui\Layui;

use Phax\Traits\Singleton;
use Phaxui\HtmlAssets;

/**
 * @link https://layui.dev/docs/2/
 */
class Layui
{
    use  Singleton;
    public string $version = '2.9.0';

    /**
     * @var array js 配置信息
     */
    public static array $globalJsWindowConfig = [];
    private array $_config = [];

    /**
     * @var bool 是否使用 include 的方式包含 Layui 目录下的 index.css, index.js, upload.css 文件
     */
    static bool $includeLocal = true;

    protected function __construct()
    {

    }

    /**
     * 添加配置信息
     * @param array $config
     * @return void
     */
    public function addWindowConfig(array $config = []): void
    {
        $this->_config = array_merge($this->_config, $config);
    }

    public function header(): void
    {
        if (HtmlAssets::$cdnJs) {
            echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/layui/'.$this->version.'/css/layui.min.css" />';
            echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />';
        } elseif (HtmlAssets::$cdn) {
            echo '<link rel="stylesheet" href="https://cdn.staticfile.org/layui/'.$this->version.'/css/layui.min.css" />';
            echo '<link rel="stylesheet" href="https://cdn.staticfile.org/font-awesome/4.7.0/css/font-awesome.min.css" />';
        } else {
            echo '<link rel="stylesheet" href="/assets/layui/layui.min.css" />';
            echo '<link rel="stylesheet" href="/assets/font-awesome-4.7.0/css/font-awesome.min.css" />';
        }

        if (self::$includeLocal) {
            echo '<style type="text/css">';
            include_once HtmlAssets::tryMinFile(__DIR__ . '/index.css');
            include_once HtmlAssets::tryMinFile(__DIR__ . '/upload.css');
            echo '</style>';
        }
    }

    private bool $hasImport = false;

    public function footer(): void
    {
        if ($this->hasImport) {
            return;
        }
        if (HtmlAssets::$cdnJs) {
            echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/layui/'.$this->version.'/layui.min.js"></script>';
        } elseif (HtmlAssets::$cdn) {
            echo '<script src="https://cdn.staticfile.org/layui/'.$this->version.'/layui.min.js"></script>';
        } else {
            echo '<script src="/assets/layui/layui.min.js"></script>';
        }

        echo '<script type="text/javascript">const $ = layui.jquery,layer = layui.layer, form = layui.form, laydate= layui.laydate,util=layui.util,table=layui.table;';

        echo 'window.CONFIG = {';
        foreach ($this->_config as $key => $v) {
            if (is_string($v)) {
                if (str_contains($v, '\'')) {
                    echo '"'.$key.'"', ':"', $v, '",';
                } else {
                    echo '"'.$key.'"', ":'", $v, "',";
                }
            } else {
                echo '"'.$key.'"', ':', $v, ',';
            }
        }
        echo '};';

        if (self::$includeLocal) {
            include_once HtmlAssets::tryMinFile(__DIR__ . '/index.js');
        }
        echo '</script>';
        $this->hasImport = true;
    }


}