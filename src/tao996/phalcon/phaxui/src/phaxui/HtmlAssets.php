<?php

namespace Phaxui;

use Phax\Foundation\Router;

class HtmlAssets
{
// 注意：这里的 cdn 只针对 layui, tinymce, awesome 等公共资源生效
    public static bool $cdnCN = false; // China cdn
    public static bool $cdnNcn = false; // not China cdn

    private static array $hasImports = [];

    private static array $headerFiles = [];
    private static array $footerFiles = [];

    public static function addHeaderFile(string $file): void
    {
        if (!in_array($file, self::$headerFiles)) {
            self::$headerFiles[] = $file;
        }
    }

    public static function addFooterFile(string $file): void
    {
        if (!in_array($file, self::$footerFiles)) {
            self::$footerFiles[] = $file;
        }
    }


    public static function outputHeaders(): void
    {
        foreach (self::$headerFiles as $file) {
            self::includeAssetsFile($file);
        }
    }

    public static function outputFooters(): void
    {
        foreach (self::$footerFiles as $file) {
            self::includeAssetsFile($file);
        }
    }

    /**
     * 引入文件（立即输出）
     * @param string $pathFile 待引入的 css 或 js 文件
     * @return bool
     */
    public static function includeAssetsFile(string $pathFile): bool
    {
        $pathFile = self::tryMinFile($pathFile);
        if (in_array($pathFile, self::$hasImports)) {
            return true;
        }
        if (file_exists($pathFile)) {
            if (str_ends_with($pathFile, 'css')) {
                echo '<style type="text/css">';
                include_once $pathFile;
                echo '</style>';
            } else {
                echo '<script>';
                include_once $pathFile;
                echo '</script>';
            }
            self::$hasImports[] = $pathFile;
            return true;
        }
        return false;
    }

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
     * @param $file string
     * @return bool
     */
    public static function addViewFile(string $file): bool
    {
        $pathFile = view()->getViewsDir() . $file;
        return self::includeAssetsFile($pathFile);
    }

    /**
     * 如果模板下存在着 js 文件，则引入它
     * @param string $theme
     * @return bool
     */
    public static function appendTemplateJs(string $theme = ''): bool
    {
        $jsFile = join('/', $theme
                ? [Router::getViewPath(), $theme, Router::getPickView()]
                : [Router::getViewPath(), Router::getPickView()]) . '.js';
        return self::includeAssetsFile($jsFile);
    }


}