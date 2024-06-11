<?php

namespace Phax\Mvc;

use Phax\Foundation\Router;
use Phax\Support\I18n\Lang;
use Phax\Support\Validate;
use Phax\Utils\MyData;

/**
 * @link https://docs.phalcon.io/5.0/en/request
 * @link https://docs.phalcon.io/5.0/en/filter-filter#constants 过滤参数
 */
class Request
{
    public static function isApiRequest(): bool
    {
        return request()->isAjax() || Router::isApiPath();
    }

    public static function isLocalhost(): bool
    {
        return str_contains($_SERVER['HTTP_HOST'], 'localhost');
    }

    public static function mustPost()
    {
        if (!request()->isPost()) {
            throw new \Exception('only support POST method', 200);
        }
    }

    /**
     * 如果有 $name 值，则优先从 getQuery 中获取值
     * @return mixed
     */
    public static function getData(string $name = null): mixed
    {
        if ($name && isset($_GET[$name])) {
            return $_GET[$name];
        }
        if (request()->isPost()) {
            if (!$data = request()->getPost()) {
                // 通过 json body 提交的数据，如小程序
                $data = request()->getJsonRawBody(true);
            }
        } else {
            $data = request()->get();
        }
        return $name ? ($data[$name] ?? null) : $data;
    }

    /**
     * @throws \Exception
     */
    public static function mustHasSet(array $data, array|string $keys): void
    {
        Validate::mustHasSet($data, $keys);
    }

    /**
     * 支持 name|名称 的格式
     * @param string $key
     * @return string[] [参数名,意义]
     * @throws \Exception
     */
    protected static function nameLang(string $key): array
    {
        if (empty($key)) {
            throw new \Exception('name is empty for Request::Get', 200);
        }
        $cc = explode('|', $key);
        return count($cc) == 1 ? [$key, $key] : $cc;

    }

    /**
     * 检查数据能否为空
     * @param $mustNotEmpty bool 是否必须不能为空
     * @param $v mixed 待检查的值
     * @param $title string 标题
     * @return void
     * @throws \Exception
     */
    protected static function checkEmptyResult(bool $mustNotEmpty, mixed $v, string $title): void
    {
        if ($mustNotEmpty && empty($v)) {
            throw new \Exception(__('require', ['field' => $title]), 200);
        }
    }

    /**
     * 获取一个整型数据，数据来源为 request()->get()，如果需要从 url 中获取，请使用 getQueryInt
     * @param string $name 示例 name 或者 name|用户名
     * @param bool $notEmpty 是否允许为空
     * @return int
     * @throws \Exception
     */
    public static function getInt(string $name, bool $notEmpty = true): int
    {
        $cc = self::nameLang($name);
        $v = intval(self::getData($cc[0]));
        self::checkEmptyResult($notEmpty, $v, $cc[1]);
        return $v;
    }

    public static function getQueryInt(string $name, bool $notEmpty = true)
    {
        $cc = self::nameLang($name);
        $v = request()->getQuery($cc[0], 'int', 0);
        self::checkEmptyResult($notEmpty, $v, $cc[1]);
        return intval($v);
    }

    public static function getQueryString(string $name, bool $notEmpty = true)
    {
        $cc = self::nameLang($name);
        $v = request()->getQuery($cc[0], 'string', '');
        self::checkEmptyResult($notEmpty, $v, $cc[1]);
        return $v;
    }

    public static function getString(string $name, bool $notEmpty = true): string
    {
        $cc = self::nameLang($name);
        $v = self::getData($cc[0]);
        self::checkEmptyResult($notEmpty, $v, $cc[1]);
        return $v ?: '';
    }

    /**
     * 获取一个整型集合
     * @param string $name
     * @param bool $notEmpty
     * @return array
     * @throws \Exception
     */
    public static function tryGetInts(string $name, bool $notEmpty = true): array
    {
        $cc = self::nameLang($name);
        $vs = request()->get($cc[0]);
        self::checkEmptyResult($notEmpty, $vs, $cc[1]);
        return MyData::getInts($vs);
    }

    /**
     * 追加分页
     * @param \Phax\Db\QueryBuilder $b
     * @return \Phax\Db\QueryBuilder
     */
    public static function pagination(\Phax\Db\QueryBuilder $b): \Phax\Db\QueryBuilder
    {
        $page = request()->get('page', 'int', 0);
        $limit = request()->get('limit', 'int', 15);
        $b->pagination($page - 1, $limit);
        return $b;
    }

    public static function getBestLanguage(): string
    {
        return Lang::getBaseLanguage();
    }
}