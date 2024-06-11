<?php

namespace Phax\Foundation;

use Phax\Support\Config;
use Phax\Support\Logger;
use Phax\Utils\MyData;

class Router
{
    /**
     * 多模块标识
     */
    const ModulePrefix = 'm';
    /**
     * 前端项目标识
     */
    const ProjectPrefix = 'p';
    /**
     * 默认的语言参数匹配表达式
     * @var string
     */
    public static string $languageRule = '/{language:[a-z]{2}}';

    /**
     * 非标准命名空间目录
     * @param string $name 模块名称
     * @param array $config 配置信息，如果模块/应用是通过 composer 安装的，那么则需要将其名称及配置添加到此处
     * @var array
     */
    private static array $vendors = [];

    public static function addVendor(string $name, array $config): void
    {
        if (key_exists($name, self::$vendors)) {
            Logger::info('Router vendors repeat:' . $name);
            return;
        }
        self::$vendors[$name] = $config;
        if (isset($config['addNamespace'])) {
            loader()->addNamespace($name, $config['addNamespace'] . $name, true)->register();
        }
    }


    /**
     * 注意，not include ajax request，如果要判断是否 ajax，还需要 request()->isAjax()
     * @return bool
     */
    public static function isApiPath(string $url = ''): bool
    {
        if ($url === '') {
            $url = $_SERVER['REQUEST_URI'];
        }

        return str_starts_with(self::filterIfLanguage($url), '/api/');
    }

    /**
     * 判断是否为多模块
     * @param string $url
     * @return bool
     */
    public static function isMultipleModules(string $url = ''): bool
    {
        if ($url === '') {
            $url = $_SERVER['REQUEST_URI'];
        }
        $url = self::filterIfLanguage($url);
        return str_starts_with($url, '/api/' . self::ModulePrefix . '/')
            || str_starts_with($url, '/' . self::ModulePrefix . '/');
    }

    /**
     * 应用 src/app/Http/Projects
     * @param string $url
     * @return bool
     */
    public static function isAppProject(string $url = ''): bool
    {
        if ($url === '') {
            $url = $_SERVER['REQUEST_URI'];
        }
        $url = self::filterIfLanguage($url);
        return str_starts_with($url, '/api/' . self::ProjectPrefix . '/')
            || str_starts_with($url, '/' . self::ProjectPrefix . '/');
    }

    public static function getProjectName(string $url, string $defProject): string
    {
        $path = substr($url, 3);
        if (empty($path)) {
            return $defProject;
        }
        $index = strpos($path, '/');
        if ($index === false) {
            return $path;
        }
        return substr($path, 0, $index);
    }

    /**
     * 如果是多语言地址，则过滤掉它
     * @param string $url
     * @return string
     */
    public static function filterIfLanguage(string $url): string
    {
        return self::hasLanguage($url) ? substr($url, 3) : $url;
    }

    /**
     * 判断是否为多语言
     * @param string $url
     * @return bool
     */
    public static function hasLanguage(string $url = ''): bool
    {
        if ($url === '') {
            $url = $_SERVER['REQUEST_URI'];
        }
        return preg_match('|^/[a-z]{2}/|', $url);
    }

    /**
     * 获取匹配到的语言
     * @param string $url
     * @return string
     */
    public static function getMatchLanguage(string $url = ''): string
    {
        if ($url === '') {
            $url = $_SERVER['REQUEST_URI'];
        }
        preg_match_all('|^/([a-z]{2})/|', $url, $match);
        return isset($match[1][0]) && $match ? $match[1][0] : '';
    }

    /**
     * 获取最佳语言
     * @param string $url 待分析的链接, 默认为 $_SERVER['REQUEST_URI']
     * @param string $default 默认语言会从 config('app.locale') 中读取
     * @return string
     */
    public static function getLanguage(string $url = '', string $default = 'cn'): string
    {
        return self::getMatchLanguage($url) ?: config('app.locale', $default);
    }

    /**
     * @param string $path path 地址，注意不能带有 ?
     * @param array $options ['module'=>多模块标识，默认为 m,'project'=> 前端应用，默认为空]
     * @throws \Exception
     */
    public static function analysisRoutePath(string $path, array $options = []): array
    {
        if (str_contains($path, '?')) {
            throw new \Exception('analysisRoute error: it should not contain "?" char');
        }

        $options = array_merge(['module' => 'm', 'project' => ''], $options);
        $isLanguage = self::hasLanguage($path);
        if ($isLanguage) {
            $path = self::filterIfLanguage($path);
        }

        $multiPrefix = '/' . $options['module'] . '/';
//        dd(__FILE__,$path,'~~~');
        if (str_starts_with($path, $multiPrefix)) {
//            $url = rtrim($url, '/');
            $path = substr($path, strlen($multiPrefix) - 1); // 去掉多模块，转为普通的单应用地址
            $blank = '/' === $path;
            if ($blank) {
                $path = '/index';
            }
            $urlElements = explode('/', $path);
            if (count($urlElements) >= 2 && end($urlElements) == "") {
                array_pop($urlElements);
                $path = rtrim($path, '/');
            }
            if (isset($urlElements[1])) {
                $urlElements[1] = MyData::formatName($urlElements['1']);
            }
            $data = [
                'pattern' => $multiPrefix . ($blank ? '' : ':module'),
                'paths' => ['module' => 'index', 'controller' => 'index', 'action' => 'index'],
                'pathsname' => ['module' => 'index', 'controller' => 'index', 'action' => 'index'],
                'namespace' => 'App\Modules\\' . $urlElements[1] . '\Controllers',
                'viewpath' => PATH_APP . 'Modules/' . $urlElements[1] . '/views',
                'module' => PATH_APP . 'Modules/' . $urlElements[1] . '/Module.php',
                'name' => $urlElements[1],
            ];
//            dd(__FILE__,substr_count($path, '/'));
//            dd(__LINE__,$url,substr_count($url, '/'));
            switch (substr_count($path, '/')) {
                case 1: // /m1 或者 /m1.subM
                    if (!$blank) {
                        $data['paths']['module'] = 1;
                        $data['pathsname']['module'] = $urlElements[1];
                        self::subMultipleModulesRoute($urlElements[1], 'index', $data);
                    }
                    break;
                case 2: // /m1/c1 或者 /m1.subM/c1
                    $data['pattern'] .= '/:controller';
                    $data['paths']['module'] = 1;
                    $data['paths']['controller'] = 2;

                    $data['pathsname']['module'] = $urlElements[1];
                    $data['pathsname']['controller'] = $urlElements[2];
                    self::subMultipleModulesRoute($urlElements[1], $urlElements[2], $data);
                    break;
                case 3: // /m1/c1/a1
                    $data['pattern'] .= '/:controller/:action';
                    $data['paths'] = ['module' => 1, 'controller' => 2, 'action' => 3];
                    $data['pathsname'] = ['module' => $urlElements[1], 'controller' => $urlElements[2], 'action' => $urlElements[3]];
                    self::subMultipleModulesRoute($urlElements[1], $urlElements[2], $data);
                    break;
                default: // /a/b/c/d
                    $data['pattern'] .= '/:controller/:action/:params';
                    $data['paths'] = ['module' => 1, 'controller' => 2, 'action' => 3, 'params' => 4];
                    $data['pathsname'] = ['module' => $urlElements[1], 'controller' => $urlElements[2], 'action' => $urlElements[3]];
                    self::subMultipleModulesRoute($urlElements[1], $urlElements[2], $data);
                    break;
            }
        } else {

            // 单模块
            $data = [
                'pattern' => '/',
                'paths' => ['controller' => 'index', 'action' => 'index'],
                'pathsname' => ['controller' => 'index', 'action' => 'index'],
                'namespace' => 'App\Http\Controllers',
                'viewpath' => PATH_APP . 'Http/views',
            ];
            // /m1/sub.c1/a1 将被切割成 ["", "m1", "sub.c1", "a1"]
            $urlElements = explode('/', $path);
//dd(__FILE__,$path,substr_count($path, '/'),$urlElements);
            switch (substr_count($path, '/')) {
                case 0:
                    break;
                case 1:
                    if ('/' != $path) {
                        $data['pattern'] = '/:controller';
                        $data['paths']['controller'] = 1;
                        $data['pathsname']['controller'] = $urlElements[1];
                    }
                    self::subAppControllerRoute($urlElements[1], $data, $options['project']);

                    break;
                case 2:
                    // 可能是 /controller/action 或者 /sub.controller/action
                    $data['pattern'] = '/:controller/:action';
                    $data['paths'] = ['controller' => 1, 'action' => 2,];
                    $data['pathsname'] = ['controller' => $urlElements[1], 'action' => $urlElements[2]];
                    self::subAppControllerRoute($urlElements[1], $data, $options['project']);

                    break;
                default:
                    /*
                     * url 可能是
                     * /controller/action/params
                     * /sub.controller/action/params
                     * /m/sub.controller/action 或者 /m/sub.controller/action/params
                     */
                    $data['pattern'] = '/:controller/:action/:params';
                    $data['paths'] = ['controller' => 1, 'action' => 2, 'params' => 3];
                    $data['pathsname'] = ['controller' => $urlElements[1], 'action' => $urlElements[2]];
                    if (str_contains($urlElements[1], '.')) {
                        self::subAppControllerRoute($urlElements[1], $data, $options['project']);
                    } elseif (str_contains($urlElements[2], '.')) { // 子模块
                        $data['namespace'] = str_replace('\\Controllers', '\\A0\\' . $urlElements[1] . '\\Controllers', $data['namespace']);
                        $data['viewpath'] = str_replace('/views', '/A0/' . $urlElements[1] . '/views', $data['viewpath']);
                        $data['subm'] = $urlElements[1];
                        $data['pathsname']['action'] = $urlElements[3];
                        self::subAppControllerRoute($urlElements[2], $data, $options['project']);
                        if (count($urlElements) == 4) {
                            $data['pattern'] = '/:controller/:action';
                            unset($data['paths']['params']);
                        }
                    }
                    break;
            }
        }
//        dd($isLanguage,$path,$data);
        if ($isLanguage) {
            $data['pattern'] = self::$languageRule . $data['pattern'];
            foreach ($data['paths'] as $key => $value) {
                if (is_integer($value)) {
                    $data['paths'][$key] = $value + 1;
                }
            }
        }
        return $data;
    }

    /**
     * 单应用默认首页/子目录
     * @param string $controller 如果为空则为默认首页
     * @param array $data
     * @return void
     */
    private static function subAppControllerRoute(string $controller, array &$data, string $project): void
    {

        $g = explode('.', $controller);
        $gc = count($g);
        if ($gc == 2) {
            $data['pathsname']['controller'] = $g[1];
            $data['namespace'] .= ('\\' . $g[0]);
            $data['subc'] = $g[0];
        } elseif ($gc > 2) {
            throw new \Exception('sub dir example: /c1/sub.a1/params');
        }

        if ($project) {
            $data['namespace'] = str_replace('Http\Controller',
                'Http\Projects\\' . $project . '\Controller',
                $data['namespace']);
            $data['viewpath'] = str_replace('Http/views',
                'Http/Projects/' . $project . '/views',
                $data['viewpath']);
        }
    }

    /**
     * 多模块的子模块/子目录功能
     * @return void
     */
    private static function subMultipleModulesRoute(string $module, string $controller, array &$data): void
    {
        $subModule = str_contains($module, '.'); // 子模块
        $subControl = str_contains($controller, '.'); // 子目录
        if ($subModule) {
            $m = explode('.', $module);
            if (count($m) != 2) {
                throw new \Exception('multi module with sub module example: /m/m1.m2/controller/action/params');
            }
//            $m[0] = MyData::formatName($m[0]);
//            $m[1] = MyData::formatName($m[1]);

            $data['pathsname']['module'] = $m[0];
            // "App\Modules\m1.m2\Controllers" => "App\Modules\m1\A0\m2\Controllers"
            $data['namespace'] = str_replace('.', '\A0\\', $data['namespace']);
            // "/var/www/app/Modules/m1.m2/views" => "/var/www/app/Modules/m1/A0/m2/views"
            $data['viewpath'] = str_replace('.', '/A0/', $data['viewpath']);
            // "/var/www/app/Modules/m1.m2/Module.php" => '/var/www/app/Modules/m1/Module.php'
            $data['module'] = str_replace($module, $m[0], $data['module']);
            $data['name'] = $m[0];
            $data['subm'] = $m[1];
            if (!$subControl) {
                return;
            }
            // /m/m1.ext/sub1.c2
//            dd($module, $controller, $data); // m1.ext, sub1.c2
        }

        // 子目录
        if ($subControl) {
            $g = explode('.', $controller);
            if (count($g) != 2) {
                throw new \Exception('multi module with sub dir example: /m/m1/ext.controller/action/params');
            }
            $data['pathsname']['controller'] = $g[1];
            $data['namespace'] .= ('\\' . $g[0]);
            $data['subc'] = $g[0];
        }
    }

    /**
     * 缓存的配置信息 (todo 移除)
     * @var array
     */
    private static array $options = [];

    /**
     * 当前请求所匹配的路由情况
     * @return array
     */
    public static function matchOptions(): array
    {
        return self::$options;
    }

    /**
     * 将配置信息注入到程序中
     * @param array $config
     * @return void
     */
    public static function addRoute(array &$config): void
    {
        $router = router();
        $router->setDefaultNamespace($config['namespace']);
        if (isset($config['registerModules'])) {
            application()->registerModules($config['registerModules']);
        }
//        dd('addRoute',$config);
        if (!$config['isApi']) {
            if (isset($config['vendor']) && 'overwrite' == $config['vendor']) {
                die('Router.php: TODO register the view in the Module.php by your self');
            } else {
                view()->setViewsDir($config['viewpath']);
                if (!isset($config['pickview'])) {
                    $config['pickview'] = self::formatPickView($config['pathsname']['controller'], $config['pathsname']['action']);
                }
                view()->pick($config['pickview']); // 你可以在控制器中随机修改
            }
        }
        $router->add($config['route'], $config['paths']);
    }

    /**
     * 分析链接
     * @param string $requestURI 待处理的 URL
     * @param array $options 配置信息  ['module'=>多模块标识，默认为 m,'project'=> 前端应用，默认为空]
     * @return array
     * @throws \Exception
     */
    public static function analysisWithURL(string $requestURI, array $options = []): array
    {
        $backupURL = $requestURI;
        $language = self::getLanguage($requestURI);
        $requestURI = self::filterIfLanguage($requestURI);

        $index = strpos($requestURI, '?');
        $requestURI = $index === false ? $requestURI : substr($requestURI, 0, $index);

        $isApi = self::isApiPath($requestURI);
        if ($isApi) {
            $requestURI = substr($requestURI, 4);
        }

        $isProject = self::isAppProject($requestURI);
        if ($isProject) {
            $options['project'] = self::getProjectName($requestURI, $options['project'] ?? '');
            $requestURI = substr($requestURI, 3 + strlen($options['project'])) ?: '/';
        }

        $config = self::analysisRoutePath($requestURI, $options);
        $config['language'] = $language;
//        dd(__LINE__, $config);
        $config['isApi'] = $isApi;
        $config['url'] = rtrim($backupURL, '/');

        if (!$isApi) {
            if (isset($config['subc'])) { // 子目录
                $config['pickview'] = $config['subc'] . '/' . self::formatPickView($config['pathsname']['controller'], $config['pathsname']['action']);
            }
        }
        // 多模块时注册模块
        $isMultipleModules = self::isMultipleModules($requestURI);
        if ($isMultipleModules) {
            $hasModule = file_exists($config['module']);
            $config['registerModules'] = [
                $config['name'] => [
                    'path' => $hasModule
                        ? $config['module']
                        : dirname(__DIR__) . '/Mvc/Module.php',
                    'className' => $hasModule
                        ? 'App\Modules\\' . $config['name'] . '\Module'
                        : 'Phax\Mvc\Module',
                ]
            ];
        }
        if (isset($config['subm'])) {
            if ($isMultipleModules) {
                $config['pattern'] = str_replace(':module', ':module\.' . $config['subm'], $config['pattern']);
            } else {
                $config['pattern'] = str_replace(':controller', $config['subm'] . '/:controller', $config['pattern']);
            }
        }
        if (isset($config['subc'])) {
            $config['pattern'] = str_replace(':controller', $config['subc'] . '\.([a-zA-Z0-9\_\-]+)', $config['pattern']);
        }
        if ($isProject) {
            $config['pattern'] = '/p/' . $options['project'] . $config['pattern'];
        }
        $config['route'] = $isApi ? '/api' . $config['pattern'] : $config['pattern'];


//        dd($config);
        return $config;
    }

    /**
     * 处理请求，通常在 bootstrap/app.php 中调用
     * @return void
     * @throws \Exception
     */
    public static function start(): void
    {
        if (IS_CLI) {
            // start with artisan
            return;
        }
        $options = [
            'module' => self::ModulePrefix,
            'project' => Config::currentProject(),
        ];
        $config = self::analysisWithURL($_SERVER['REQUEST_URI'], $options);
        self::doVendors($config);
//        dd($config);
        self::addRoute($config);
        self::$options = $config;
    }

    private static function doVendors(array &$config): void
    {
        if (self::$vendors) {
            if (isset($config['name'])) {
                $name = $config['name'];
                if (isset($config['module']) && isset(self::$vendors[$name])) {
                    $config = array_merge($config, self::$vendors[$name]);
                    $config['vendor'] = 'overwrite';
                }
            }
        }
    }

    private static array $cmd = [];

    /**
     * 添加命令
     * @param string $name 名称
     * @param string|callable $action 所执行的命令或回调函数
     * @return void
     */
    public static function addCLI(string $name, string|callable $action, bool $overwrite = false): void
    {
        if (!$overwrite && isset(self::$cmd[$name])) {
            throw new \Exception('repeat CLI:' . $name);
        }
        self::$cmd[$name] = [$action];
    }

    /**
     * @param string $name 待执行命令的名称
     * @return bool
     */
    public static function runCLI(string $name): bool
    {
        global $argv;
        if (isset(self::$cmd[$name])) {
            $info = self::$cmd[$name];
            if (is_callable($info[0])) {
                $info[0](); // 调用函数
            } else {
                system($info[0] . ' ' . join(' ', array_slice($argv, 2)), $code);
            }
            return true;
        }
        return false;
    }

    /**
     * @param string $route
     * @param array $options ['m'=>'模块名称','project'=>前端项目名称']
     * @return array
     * @throws \Exception
     */
    public static function analysisWithCLI(string $route, array $options = [])
    {
        $options = array_merge(['m' => '', 'project' => ''], $options);

        $arguments = [
            'task' => 'main', 'action' => 'index',
            'namespace' => 'App\Console'
        ];
        $route = ltrim($route, '/');
        if (empty($route)) {
            if ($options['project']) {
                $arguments['namespace'] = 'App\Http\Projects\\' . $options['project'] . '\Console';
            }
            return $arguments;
        }
        $items = explode('/', $route);
        if ($items[0] == $options['m']) { // 多模块
            if (empty($items[1])) {
                throw new \Exception('必须指定模块名称');
            }
            $subM = '';
            if (str_contains($items[1], '.')) {
                $ss = explode('.', $items[1]);
                $items[1] = $ss[0];
                $subM = $ss[1];
            }
            $arguments['module'] = $items[1];
            $path = PATH_APP_MODULES . $items[1] . '/Module.php';
            $hasModule = file_exists($path);

            $arguments['modules'] = [
                $items[1] => [
                    'path' => $hasModule ? $path
                        : dirname(__DIR__) . '/Mvc/Module.php',
                    'className' => $hasModule
                        ? 'App\Modules\\' . $items[1] . '\Module'
                        : 'Phax\Mvc\Module',

                ]
            ];
            if ($subM) {
                $arguments['namespace'] = 'App\Modules\\' . $items[1] . '\A0\\' . $subM . '\Console';
            } else {
                $arguments['namespace'] = 'App\Modules\\' . $items[1] . '\Console';
            }
            $arguments['task'] = $items[2] ?? 'main';
            $arguments['action'] = $items[3] ?? 'index';
        } else {
            if ($options['project']) {
                $arguments['namespace'] = 'App\Http\Projects\\' . $options['project'] . '\Console';
            }
            $arguments['task'] = $items[0];
            $arguments['action'] = $items[1] ?? 'index';
        }

        return $arguments;
    }

    /**
     * 获取当前访问节点命名（通常用于做权限管理）
     * @param array $options 默认为 Router::$options
     * @return string
     */
    public static function getNode(array $options = []): string
    {
        if (empty($options)) {
            $options = self::$options;
        }
        $isSubM = isset($options['subm']); // 子模块
        $isSubC = isset($options['subc']); // 子目录
        if (isset($options['module'])) {
            if (!$isSubM && !$isSubC) {
                return join('/', $options['pathsname']);
            }
            if ($isSubM && !$isSubC) {
                return join('/', [
                    $options['pathsname']['module'] . '.' . $options['subm'],
                    $options['pathsname']['controller'],
                    $options['pathsname']['action']
                ]);
            }
            if (!$isSubM && $isSubC) {
                return join('/', [
                    $options['pathsname']['module'],
                    $options['subc'] . '.' . $options['pathsname']['controller'],
                    $options['pathsname']['action']
                ]);
            }
            if ($isSubM && $isSubC) {
                return join('/', [
                    $options['pathsname']['module'] . '.' . $options['subm'],
                    $options['subc'] . '.' . $options['pathsname']['controller'],
                    $options['pathsname']['action']
                ]);
            }
        } else {
            if (!$isSubM && !$isSubC) {
                return join('/', $options['pathsname']);
            }
            if (!$isSubM && $isSubC) {
                return join('/', [
                    $options['subc'] . '.' . $options['pathsname']['controller'],
                    $options['pathsname']['action'],
                ]);
            }
            if ($isSubM && $isSubC) {
                return join('/', [
                    $options['subm'],
                    $options['subc'] . '.' . $options['pathsname']['controller'],
                    $options['pathsname']['action'],
                ]);
            }
        }
        return '';
    }

    /**
     * 格式化控制器/操作名称
     * @param string $name refreshNode, refresh-node, refresh_node, refreshNodeAction, refreshNodeController
     * @return string refreshNode
     */
    public static function formatNodeName(string $name, bool $lcfirst = true): string
    {
        if (str_ends_with($name, '.php')) {
            $name = substr($name, 0, -4);
        }
        if (str_ends_with($name, 'Action')) {
            $name = substr($name, 0, -6);
        } elseif (str_ends_with($name, 'Controller')) {
            $name = substr($name, 0, -10);
        }
        return MyData::formatName($name, $lcfirst);
    }

    /**
     * 视图文件所在目录
     * @return string
     */
    public static function getViewPath(): string
    {
        return self::$options['viewpath'];
    }

    // 统一格式 someCtrl/someAction
    private static function formatPickView($controller, $action): string
    {
        $cName = self::formatNodeName($controller);
        $aName = self::formatNodeName($action);
        return $cName . '/' . $aName;
    }

    /**
     * 返回当前渲染的模板的名称
     * @param bool $actualPick 实际渲染的模板
     * @return string open.form/rent
     */
    public static function getPickView(bool $actualPick = false): string
    {

        if ($actualPick && !empty(self::$options['pickview'])) {
            return self::$options['pickview'];
        }
        if (isset(self::$options['subc'])) { // 子目录
            return self::$options['subc'] . '/' . self::formatPickView(self::$options['pathsname']['controller'], self::$options['pathsname']['action']);
        } else {
            return self::$options['pathsname']['controller'] . '/' . self::$options['pathsname']['action'];
        }
    }

    public static function getPathViewTPL(): string
    {
        $f = self::getViewPath() . '/' . self::getPickView();
        foreach (['.phtml', '.php', '.volt'] as $suf) {
            if (file_exists($f . $suf)) {
                return $f . $suf;
            }
        }
        return '';
    }

    /**
     * 渲染指定的模板（必须存在 self::$options['viewpath'] 目录下），不会修改当前路由的 action 名称
     * @param string $pathname 模板名称
     * @return void
     */
    public static function changePickView(string $pathname): void
    {
        self::$options['pickview'] = $pathname;
        view()->pick(self::$options['pickview']);
    }

    public static function currentProject($default = '')
    {
        return Config::currentProject($default);
    }

}