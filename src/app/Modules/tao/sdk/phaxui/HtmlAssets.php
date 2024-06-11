<?php

namespace App\Modules\tao\sdk\phaxui;

use Phax\Foundation\Router;
use Phax\Support\Logger;

class HtmlAssets
{

    // 注意：这里的 cdn 只针对 layui, tinymce, awesome 等公共资源生效
    public static string $cdn = '/assets/';

    /**
     * 设置 CDN 地址
     * @param string $cdn 默认从 app.cdn 读取，可选 cn|ncn
     * @return void
     */
    public static function initWithCdn(string $cdn = ''): void
    {
        if ($cdn === '') {
            $cdn = config('app.cdn');
        }
        if ($cdn) {
            self::$cdn = match ($cdn) {
                'cn' => 'https://cdn.staticfile.org/',
                'ncn' => 'https://cdnjs.cloudflare.com/ajax/libs/',
                default => str_ends_with($cdn, '/') ? $cdn : $cdn . '/',
            };
        }
    }

    private static array $hasImports = [];

    private static array $headerFiles = [];
    private static array $footerFiles = [];

    /**
     * 添加文件到头部
     * @param string $file
     * @param int $weight 权重
     * @param string $type 类型，css|js
     * @return void
     */
    public static function addHeaderFile(string $file, int $weight = 0, string $type = ''): void
    {
        if (!in_array($file, self::$headerFiles)) {
            self::$headerFiles[] = [$file, $weight, $type];
        }
    }

    /**
     * 添加文件到底部
     * @param string $file
     * @param int $weight 权重
     * @param string $type css|js
     * @return void
     */
    public static function addFooterFile(string $file, int $weight = 0, string $type = ''): void
    {
        if (!in_array($file, self::$footerFiles)) {
            self::$footerFiles[] = [$file, $weight, $type];
        }
    }

    private static function sortByWeight(array &$data)
    {
        usort($data, function ($v1, $v2) {
            return $v1[1] - $v2[1];
        });
    }

    /**
     * 输出头部脚本样式
     * @return void
     */
    public static function outputHeaders(): void
    {
        self::sortByWeight(self::$headerFiles);
        foreach (self::$headerFiles as $file) {
            self::includeAssetsFile($file[0], $file[2]);
        }
    }

    /**
     * 输入底部脚本样式
     * @return void
     */
    public static function outputFooters(): void
    {
        self::sortByWeight(self::$footerFiles);
        foreach (self::$footerFiles as $file) {
            self::includeAssetsFile($file[0], $file[2]);
        }
    }


    public static function includeAssetsFile(string $file, string $type = ''): bool
    {
        if (in_array($file, self::$hasImports)) {
            return false;
        }
        if ($type == '') {
            if (str_ends_with($file, '.css')) {
                $type = 'css';
            } elseif (str_ends_with($file, '.js')) {
                $type = 'js';
            } else {
                Logger::warning('unknown assets file type for:' . $file);
                return false;
            }
        }
        $http = str_starts_with($file, 'https://') || str_starts_with($file, 'http://');
        if (!$http) {
            $file = self::tryMinFile($file); // 检查是否存在压缩文件
            if (!file_exists($file)) {
                return false;
            }
        }
        if ('css' == $type) {
            if ($http) {
                echo '<link rel="stylesheet" type="text/css" href="', $file, '">';
            } else {
                echo '<style type="text/css">';
                include_once $file;
                echo '</style>';
            }
        } elseif ('js' == $type) {
            if ($http) {
                echo '<script src="', $file, '"></script>';
            } else {
                echo '<script>';
                include_once $file;
                echo '</script>';
            }
        } else {
            Logger::warning('include invalid assets type for:' . $file);
            return false;
        }
        self::$hasImports[] = $file;
        return true;
    }

    /**
     * 尝试引用 min 压缩文件
     * @param string $file
     * @return string
     */
    public static function tryMinFile(string $file): string
    {
        if (str_ends_with($file, '.min.js') || str_ends_with($file, '.min.css')) {
            return $file;
        }
        $minFile = str_replace(['.css', '.js'], ['.min.css', '.min.js'], $file);
        return file_exists($minFile) ? $minFile : $file;
    }

    /**
     * 添加当前视图目录下的文件
     * @param $file string 待添加文件名称，如 tao.css
     * @return bool
     */
    public static function addViewFile(string $file): bool
    {
        $pathFile = view()->getViewsDir() . $file;
        return self::includeAssetsFile($pathFile);
    }

    /**
     * @var string 指定要加载的脚本
     */
    public static string $pickName = '';

    /**
     * 如果当前模板下存在着同名 js 文件，则引入它；比如你的模板为 add.phtml，如果存在 add.js 则会引入它
     * @param string $theme 主题
     * @return bool
     */
    public static function appendTemplateJs(string $theme = ''): bool
    {

        $pickName = self::$pickName ?: Router::getPickView(true);
        $jsFile = join('/', $theme
                ? [Router::getViewPath(), $theme, $pickName]
                : [Router::getViewPath(), $pickName]) . '.js';
        return self::includeAssetsFile($jsFile, 'js');
    }


}