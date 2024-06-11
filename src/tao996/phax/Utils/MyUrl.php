<?php

namespace Phax\Utils;

use Phalcon\Mvc\Url\UrlInterface;
use Phax\Foundation\Application;
use Phax\Foundation\Router;
use Phax\Support\Facade;

/**
 * @method static UrlInterface setBasePath(string $basePath)
 * @method static UrlInterface setBaseUri(string $uri)
 * @method static UrlInterface setStaticBaseUri(string $staticBaseUri)
 * @method static string getStaticBaseUri() 域名，即 config('app.url')
 * @method static string getStatic(string|array $option) staticBaseUri+$option
 * @method static string getBaseUri()
 * @method static string getBasePath()
 * @method static string get($uri = null, $args = null, bool $local = null, $baseUri = null)
 * @method static string path(string $path = null)
 */
class MyUrl extends Facade
{

    protected static function getFacadeObject()
    {
        return Application::di()->get('url');
    }

    protected static function getFacadeName(): string
    {
        return 'url';
    }

    /**
     * 生成一个 URL 地址
     * @param string $path 路径
     * @param array|string $query 查询参数
     * @param string $prefix 前缀
     * @param string $api api 名称，通常为 api
     * @param string $language 语言 en|cn
     * @param string $origin 域名
     * @return string
     */
    public static function createPath(string $path, array|string $query = [], string $prefix = '', string $api = '', string $language = '', string $origin = ''): string
    {
        $items = [];
        if ($language) {
            $items[] = $language;
        }
        if ($api) {
            $items[] = $api;
        }
        if ($prefix) {
            $items[] = $prefix;
        }
        if ($items) {
            $url = '/' . join('/', $items) . '/' . ltrim($path, '/');
        } else {
            $url = '/' . ltrim($path, '/');
        }
        if ($query) {
            $q = is_array($query) ? http_build_query($query) : $query;
            $url = str_contains($url, '?') ? $url . '&' . $q : $url . '?' . $q;
        }
        return $origin ? rtrim($origin, '/') . $url : $url;
    }

    /**
     * 为当前页面生成相匹配的链接地址（包含了语言）
     * @param string $path 待生成的路径
     * @param array|string $query 查询的参数
     * @param bool $api 是否生成为 api 地址
     * @param string $prefix 前缀
     * @param bool $baseUri 是否包含 http 地址
     * @return string
     */
    public static function createPagePath(string $path, array|string $query = [], bool $api = false, string $prefix = '', bool $baseUri = false): string
    {

        $origin = ltrim(self::getFacadeObject()->getStaticBaseUri(), '/');
        $language = Router::hasLanguage() ? Router::getMatchLanguage() : '';

        return self::createPath($path, $query,
            $prefix,
            $api ? 'api' : '',
            $language,
            $baseUri ? $origin : ''
        );
    }

    /**
     * 生成多模块路径
     * @param string $path
     * @param bool $api
     * @param array $query
     * @return string
     */
    public static function getMultiPath(string $path, bool $api = false, array $query = []): string
    {
        return self::createPagePath($path, $query, $api, Router::ModulePrefix, false);
    }

    /**
     * 生成多模块 URL 地址
     * @param string $path
     * @param array $query
     * @return string
     */
    public static function getMultiURL(string $path, array $query = []): string
    {
        return self::createPagePath($path, $query, false, Router::ModulePrefix, true);
    }

    /**
     * 生成多模块 api URL 地址
     * @param string $path
     * @param array $query
     * @return string
     */
    public static function getMultiApiURL(string $path, array $query = []): string
    {
        return self::createPagePath($path, $query, true, Router::ModulePrefix, true);
    }

    /**
     * 生成单应用路径
     * @param string $path
     * @param bool $api
     * @param array $query
     * @return string
     */
    public static function getAppPath(string $path, bool $api = false, array $query = []): string
    {
        return self::createPagePath($path, $query, $api, Router::ProjectPrefix, false);
    }

    /**
     * 生成单应用 URL 地址
     * @param string $path
     * @param array $query
     * @return string
     */
    public static function getAppURL(string $path, array $query = []): string
    {
        return self::createPagePath($path, $query, false, Router::ProjectPrefix, true);
    }

    /**
     * 生成单应用 api URL 地址
     * @param string $path
     * @param array $query
     * @return string
     */
    public static function getAppApiURL(string $path, array $query = []): string
    {
        return self::createPagePath($path, $query, true, Router::ProjectPrefix, true);
    }
}