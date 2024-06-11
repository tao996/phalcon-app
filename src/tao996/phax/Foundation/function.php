<?php

define('IS_CLI', php_sapi_name() === 'cli');
define('IS_WEB', isset($_SERVER['HTTP_HOST']));

if (!function_exists('dd')) {
    function dd($var): void
    {
        array_map(function ($x) {
            $string = (new \Phalcon\Support\Debug\Dump())->variable($x);
            echo IS_CLI ? strip_tags($string) . PHP_EOL : $string;
        }, func_get_args());

        if (func_get_args()[func_num_args() - 1] === false) {
            return;
        } else {
            appExit();
        }
    }
}
if (!function_exists('pr')) {
    function pr($var): void
    {
        echo IS_CLI ? '|<--- ' . PHP_EOL : '<pre>';
        foreach (func_get_args() as $arg) {
            print_r($arg);
            echo IS_CLI ? PHP_EOL : '<br/>';
        }
        echo IS_CLI ? '|---> ' . PHP_EOL : '</pre>';

        if (func_get_args()[func_num_args() - 1] === false) {
            return;
        } else {
            appExit();
        }
    }
}

if (!function_exists('di')) {
    function di(): \Phalcon\Di\FactoryDefault
    {
        return \Phax\Foundation\Application::di();
    }
}

if (!function_exists('env')) {
    /**
     * 读取环境变量
     * @param $key
     * @param $default
     * @return array|false|mixed|string|null
     */
    function env($key, $default = null)
    {
        return \Phax\Support\Env::find($key, $default);
    }
}

if (!function_exists('config')) {
    /**
     * 获取配置信息，如果需要获取数组，还需要 ->toArray()
     */
    function config($path, $default = null): Phalcon\Config\Config|string|bool|int
    {
        return \Phax\Support\Config::find($path, $default);
    }
}
// ----------- 默认的 Phalcon\Di\Service
if (!function_exists('annotations')) {
    function annotations(): Phalcon\Annotations\Adapter\Memory
    {
        return \Phax\Foundation\Application::di()->getShared('annotations');
    }
}
if (!function_exists('assets')) {
    function assets(): Phalcon\Assets\Manager
    {
        return \Phax\Foundation\Application::di()->getShared('assets');
    }
}

if (!function_exists('crypt')) {
    function crypt(): \Phalcon\Encryption\Crypt
    {
        return \Phax\Foundation\Application::di()->getShared('crypt');
    }
}
if (!function_exists('cookies')) {
    function cookies(): \Phalcon\Http\Response\Cookies
    {
        return \Phax\Foundation\Application::di()->getShared('cookies');
    }
}
if (!function_exists('dispatcher')) {
    function dispatcher(): \Phalcon\Dispatcher\AbstractDispatcher
    {
        return \Phax\Foundation\Application::di()->getShared('dispatcher');
    }
}

if (!function_exists('escaper')) {
    function escaper(): Phalcon\Html\Escaper
    {
        return \Phax\Foundation\Application::di()->getShared('escaper');
    }
}

if (!function_exists('eventsManager')) {
    function eventsManager(): \Phalcon\Events\Manager
    {
        return \Phax\Foundation\Application::di()->getShared('eventsManager');
    }
}

if (!function_exists('flash')) {
    /**
     * 闪存有两种，Direct（默认）只在当前流程内有效，不能跳转；Session 可用于跳转 redirect
     * @link https://docs.phalcon.io/5.0/en/flash#adapters
     * @return \Phalcon\Flash\Direct
     */
    function flash(): \Phalcon\Flash\AbstractFlash
    {
        return \Phax\Foundation\Application::di()->getShared('flash');
    }
}

if (!function_exists('filter')) {
    function filter(): Phalcon\Filter\Filter
    {
        return \Phax\Foundation\Application::di()->getShared('filter');
    }
}

if (!function_exists('helper')) {
    function helper(): Phalcon\Support\HelperFactory
    {
        return \Phax\Foundation\Application::di()->getShared('helper');
    }
}

if (!function_exists('modelsManager')) {
    function modelsManager(): Phalcon\Mvc\Model\Manager
    {
        return \Phax\Foundation\Application::di()->getShared('modelsManager');
    }
}

if (!function_exists('modelsMetadata')) {
    function modelsMetadata(): Phalcon\Mvc\Model\MetaData\Memory
    {
        return \Phax\Foundation\Application::di()->getShared('modelsMetadata');
    }
}

if (!function_exists('request')) {
    function request(): \Phalcon\Http\RequestInterface
    {
        return \Phax\Foundation\Application::di()->getShared('request');
    }
}
if (!function_exists('response')) {
    function response(): Phalcon\Http\Response
    {
        return \Phax\Foundation\Application::di()->getShared('response');
    }
}

if (!function_exists('router')) {
    function router(): \Phalcon\Cli\Router|\Phalcon\Mvc\Router
    {
        return \Phax\Foundation\Application::di()->getShared('router');
    }
}

if (!function_exists('security')) {
    /**
     * 通常生成生成/校验表单 token；对密码进行加密处理
     * Random 生成随机数据；Hash 数据加密
     * Token 用于防止 CSRF 攻击；
     * https://docs.phalcon.io/5.0/en/encryption-security#random
     * @return \Phalcon\Encryption\Security
     */
    function security(): Phalcon\Encryption\Security
    {
        return \Phax\Foundation\Application::di()->getShared('security');
    }
}

if (!function_exists('tag')) {
    /**
     * 用于生成 HTML 代码，一般很少用到
     * @link https://docs.phalcon.io/5.0/en/html-tagfactory
     * @return \Phalcon\Html\TagFactory
     */
    function tag(): Phalcon\Html\TagFactory
    {
        return \Phax\Foundation\Application::di()->getShared('tag');
    }
}

if (!function_exists('transactionManager')) {
    function transactionManager(): Phalcon\Mvc\Model\Transaction\Manager
    {
        return \Phax\Foundation\Application::di()->getShared('transactionManager');
    }
}

if (!function_exists('url')) {
    /**
     * 通常用来拼接以便生成模块 URLs 地址; 更多功能请使用 \Phax\Utils\MyUrl
     * @param string $path 路径：模块/控制器/操作 或者 /控制器/操作
     * @param bool $api 是否为 api 地址，默认为 false
     * @param bool $multi 是否为多模块，默认为 true
     * @param array|string $query 请求参数
     * @return string
     */
    function url(string $path, bool $api = false, bool $multi = true, array|string $query = [], bool $baseUri = false): string
    {
        return \Phax\Utils\MyUrl::createPagePath($path, $query, $api,
            $multi ? \Phax\Foundation\Router::ModulePrefix : '',
            $baseUri);
    }

    /**
     * 生成 Project 链接地址
     * @return string
     */
    function projectURL(string $path, bool $api = false, array|string $query = [], bool $baseUri = false): string
    {
        return \Phax\Utils\MyUrl::createPagePath($path, $query, $api, \Phax\Foundation\Router::ProjectPrefix, $baseUri);
    }
}

// 自定义有服务
if (!function_exists('application')) {
    function application(): \Phalcon\Mvc\Application|\Phalcon\Cli\Console
    {
        return \Phax\Foundation\Application::di()->getShared('application');
    }
}

if (!function_exists('db')) {
    function db(): Phalcon\Db\Adapter\Pdo\AbstractPdo
    {
        return \Phax\Foundation\Application::di()->get('db');
    }
}
if (!function_exists('pdo')) {
    function pdo(): \PDO
    {
        return \Phax\Foundation\Application::di()->get('pdo');
    }
}
if (!function_exists('redis')) {
    function redis(): \Redis
    {
        return \Phax\Foundation\Application::di()->get('redis');
    }
}

if (!function_exists('logger')) {
    // 应用程序日志
    function logger(): \Phalcon\Logger\Logger
    {
        return \Phax\Foundation\Application::di()->get('logger');
    }
}

if (!function_exists('session')) {
    /**
     * 如果作用域在控制器之内，则可以使用 persistent
     * @link https://docs.phalcon.io/5.0/en/controllers#session
     * @return \Phalcon\Session\Manager
     */
    function session(): \Phalcon\Session\Manager
    {
        return \Phax\Foundation\Application::di()->get('session');
    }

    function sessionWith($path, $default = null)
    {
        /**
         * @var $session \Phalcon\Session\Manager
         */
        $session = \Phax\Foundation\Application::di()->get('session');
        $keys = explode('.', $path);
        $current = $session->get(array_shift($keys));
        while ($key = array_shift($keys)) {
            if (isset($current[$key])) {
                $current = $current[$key];
            } else {
                return $default;
            }
        }
        return $current;
    }
}

if (!function_exists('cache')) {
    function cache(): \Phalcon\Cache\Cache
    {
        return \Phax\Foundation\Application::di()->get('cache');
    }
}
if (!function_exists('view')) {
    // 为模板赋值，通常在控制器中使用
    function view($data = []): \Phalcon\Mvc\View
    {
        /**
         * @var \Phalcon\Mvc\View $view
         */
        $view = \Phax\Foundation\Application::di()->get('view');
        if (empty($data)) {
            return $view;
        }
        return $view->setVars($data, true);
    }
}

if (!function_exists('metadata')) {
    function metadata(): \Phalcon\Mvc\Model\MetaData
    {
        return \Phax\Foundation\Application::di()->get('modelsMetadata');
    }
}

if (!function_exists('json')) {
    // 输出 JSON  内容，通常在控制器中使用
    // 注意：，如果你不是在控制器调用 \json()；那么则需要手动 exit
    // 否则会出现 Phalcon\Http\Response\Exception: Response was already sent
    function json($data): \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
    {
//        view()->disable();
        return response()
            ->setContentType('application/json', 'UTF-8')
            ->setContent(json_encode($data))
            ->send();
    }
}

if (!function_exists('profiler')) {
    // 分析 SQL 性能
    function profiler(): \Phalcon\Db\Profiler
    {
        return \Phax\Foundation\Application::di()->getShared('profiler');
    }
}
if (!function_exists('formData')) {
    /**
     * 用于保存用户在表单中填写的内容
     * @param string $name
     * @param mixed $default 默认值
     * @param array $data 默认为 $_POST
     * @return mixed
     */
    function formData(string $name, $default = '', array $data = [])
    {
        if (empty($data)) {
            return $_POST[$name] ?? $default;
        }
        return $data[$name] ?? $default;
    }
}
if (!function_exists('viewData')) {
    function viewData(string $path = '', $default = '')
    {
        static $viewData = null;
        if (is_null($viewData)) {
            $viewData = view()->getParamsToView();
        }
        return empty($path) ? $viewData : \Phax\Utils\MyData::findWithPath($viewData, $path, $default);
    }
}

if (!function_exists('__')) {
    /**
     * 翻译：可以查看 Support/I18n/TransactionTest.php 示例
     * @param string $key
     * @param array $placeholders
     * @param string $defMessage
     * @return string
     * @throws Exception
     */
    function __(string $key, array $placeholders = [], string $defMessage = ''): string
    {
        return \Phax\Support\I18n\Transaction::get($key, $placeholders, $defMessage);
    }
}

if (!function_exists('ddRouterMatch')) {
    /**
     * 当控制器出现一些异常（视图不显示等）可打印此信息帮助排查
     * @return void
     */
    function ddRouterMatch(): void
    {
        dd('RouterInfo', \Phax\Foundation\Router::matchOptions());
    }
}
/**
 * 是否禁用 exit/die 函数，通常用在 swoole/workman 中
 * @return bool
 */
function disableExit(): bool
{
    return defined('DISABLE_EXIT') && DISABLE_EXIT;
}

/**
 * 用于在程序中代替 die/exit
 */
if (!function_exists('appExit')) {
    function appExit(string $message = ''): void
    {
        if (disableExit()) {
            throw new \Phax\Support\Exception\BlankException($message);
        } else {
            exit($message);
        }
    }
}