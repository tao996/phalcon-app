<?php

namespace Phax\Foundation;

use Phalcon\Di\FactoryDefault;
use Phax\Events\Db;
use Phax\Events\Profiler;
use Phax\Mvc\Response;
use Phax\Support\Config;
use Phax\Support\Env;
use Phax\Support\Exception\BlankException;
use Phax\Support\I18n\Transaction;
use Phax\Support\Logger;

class Application
{

    public static function di(): FactoryDefault
    {
        static $_di = null;
        if (is_null($_di)) {
            if (IS_WEB) {
                $_di = new FactoryDefault();
            } elseif (IS_CLI) {
                $_di = new FactoryDefault\Cli();
            } else {
                appExit('unknown php runtime, create di service failed');
            }
        }
        return $_di;
    }

    /**
     * @param string $sourceRoot 源码根目录
     * in docker , the basePath is /var/www, which put you source code files
     */
    public function __construct(string $sourceRoot)
    {
        if (!file_exists($sourceRoot)) {
            appExit('could not find the source root path： /xxx/src => /var/www');
        }
    }

    /**
     * 加载各种配置
     * @return void
     */
    public function autoloadServices(): void
    {
        Env::load();
        define('IS_DEBUG', (bool)env('APP_DEBUG', false));// 通常用在本地开发
        $config = Config::parse();
        $namespaces = $config->path('app.namespaces');
        if (is_array($namespaces) && !empty($namespaces)) {
            loader()->setNamespaces($namespaces, true)->register();
        }

        // 加载语言
        Transaction::getInstance()
            ->addDictionary(PATH_PHAX . 'messages/:lang.php')
            ->setLanguage($config->path('app.locale', 'cn'))
            ->loadLast();

        $di = static::di();

        $di->setShared('config', $config);
        date_default_timezone_set(\config('app.timezone'));

        $di->setShared('db', function () {
            $driver = \config('database.default');
            $class = 'Phalcon\Db\Adapter\Pdo\\' . $driver;
            $params = \config('database.stores.' . $driver)->toArray();
//            dd($class,$params);
            return new $class($params);
        });

        $di->setShared('pdo', function () {
            $driver = \config('database.default');
            $params = \config('database.stores.' . $driver)->toArray();
//            dd($driver,$params);
            switch ($driver) {
                case 'mysql':
                    $dsn = 'mysql:host=' . $params['host'] . ';port=' . $params['port'] . ';dbname=' . $params['dbname'] . ';charset=' . $params['charset'];

                    $pdo = new \PDO($dsn, $params['username'], $params['password'], [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                    ]);
                    // 不要将 int 字段转为 string
                    $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
                    $pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);

                    // 在非生产环境下，取消预处理功能，性能下降，但可以看到最终的 sql 语句
                    // 或者你可以通过函数  getRawPdoSql 来打印预处理的语句
                    if (defined('IS_DEBUG') && IS_DEBUG) {
                        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
                    }
                    return $pdo;
                case 'postgresql':
                    $dsn = "pgsql:host={$params['host']};port={$params['port']};dbname={$params['dbname']};user={$params['username']};password={$params['password']}";
                    $pdo = new \PDO($dsn);
                    $pdo->exec("set names utf8");
                    return $pdo;
                case 'sqlite':
                    $dsn = "sqlite:{$params['dbname']}";
                    return new \PDO($dsn, null, null,
                        array(\PDO::ATTR_PERSISTENT => true)
                    );
            }
            throw new \Exception('PDO Service wat not create in Di');
        });

        // redis
        $di->setShared('redis', function () {
            $cc = \config('redis')->toArray();
            $redis = new \Redis();
            $redis->connect($cc['host'], $cc['port']);
            if (!empty($cc['auth'])) {
                $redis->auth($cc['auth']);
            }
            $redis->select($cc['index']);
            $redis->persist($cc['persistent']);
            $redis->_prefix($cc['prefix']);
            return $redis;
        });

        // cache
        // https://docs.phalcon.io/5.0/en/cache
        $di->setShared('cache', function () {
            $factory = new \Phalcon\Storage\SerializerFactory();
            $cc = \config('cache')->toArray();
            $options = $cc['stores'][$cc['driver']];
            switch ($cc['driver']) {
                case 'redis':
                    $adapter = new \Phalcon\Cache\Adapter\Redis($factory, $options);
                    break;
                case 'stream':
                    $adapter = new \Phalcon\Cache\Adapter\Stream($factory, $options);
                    break;
                case 'memory':
                    $adapter = new \Phalcon\Cache\Adapter\Memory($factory, $options);
                    break;
                case 'memcached':
                    $adapter = new \Phalcon\Cache\Adapter\Libmemcached($factory, $options);
                    break;
                case 'apcu':
                    $adapter = new \Phalcon\Cache\Adapter\Apcu($factory, $options);
                    break;
                default:
                    $adapterFactory = new \Phalcon\Cache\AdapterFactory($factory);
                    $adapter = $adapterFactory->newInstance($cc['driver'], $options);
            }
            return new \Phalcon\Cache\Cache($adapter);
        });


        // 应用程序错误日志
        $di->setShared('logger', function () {
            $cc = \config('logger')->toArray();
            $params = $cc['stores'][$cc['driver']];

            switch (strtolower($cc['driver'])) {
                case 'stream':
                    $path = $params['path'];
                    preg_match('|{(\w+)}|', $path, $matches);
                    if (!empty($matches)) {
                        $path = str_replace($matches[0], date($matches[1]), $path);
                    }
                    $adapter = new \Phalcon\Logger\Adapter\Stream($path);
                    break;
                case 'syslog':
                    $adapter = new \Phalcon\Logger\Adapter\Syslog(
                        $params['ident'],
                        ['option' => LOG_NDELAY, 'facility' => LOG_MAIL]
                    );
                    break;
                case 'noop':
                    $adapter = new \Phalcon\Logger\Adapter\Noop('nothing');
                    break;
                default:
                    $adapter = new \Phalcon\Logger\Adapter\Stream('php://stderr');
            }
            // https://docs.phalcon.io/5.0/en/logger#creating-a-logger
            return new \Phalcon\Logger\Logger(
                $params['level'],
                [
                    $params['name'] => $adapter,
                ]
            );
        });

        // 注册加密
        $di->setShared('crypt', function () {
            $cc = \config('crypt')->toArray();

            $crypt = new \Phalcon\Encryption\Crypt();
            if ($cc['key']) {
                $crypt->setKey($cc['key']);
            }
            if ($cc['padding']) {
                $crypt->setPadding($cc['padding']);
            }
            $crypt->setCipher($cc['cipher']);
            return $crypt;
        });

        $di->setShared('security', function () {
            return new \Phalcon\Encryption\Security();
        });

        // https://docs.phalcon.io/5.0/en/db-models-metadata
        $di->setShared('modelsMetadata', function () {
            $cc = \config('metadata')->toArray();
            switch ($cc['driver']) {
                case 'apcu':
                    $factory = new \Phalcon\Storage\SerializerFactory();
                    $adapter = new \Phalcon\Cache\AdapterFactory($factory);
                    return new \Phalcon\Mvc\Model\MetaData\Apcu($adapter, $cc['stores']['apcu']);
                case 'memcached':
                    $factory = new \Phalcon\Storage\SerializerFactory();
                    $adapter = new \Phalcon\Cache\AdapterFactory($factory);
                    return new \Phalcon\Mvc\Model\MetaData\Libmemcached($adapter, $cc['stores']['memcached']);
                case 'redis':
                    $factory = new \Phalcon\Storage\SerializerFactory();
                    $adapter = new \Phalcon\Cache\AdapterFactory($factory);
                    return new \Phalcon\Mvc\Model\MetaData\Redis($adapter, $cc['stores']['redis']);
                case 'stream':
                    return new \Phalcon\Mvc\Model\MetaData\Stream($cc['stores']['stream']);
                default:
                    return new \Phalcon\Mvc\Model\Metadata\Memory();
            }
        });

        $di->setShared('profiler', function () {
            return new \Phalcon\Db\Profiler();
        });

        if (IS_WEB) {
            $this->addWebServices($di);

        } elseif (IS_CLI) {
            $di->setShared('application', function () {
                return new \Phalcon\Cli\Console();
            });
        }
        $dbLogDriver = \config('database.log.driver');
        if ('file' === $dbLogDriver) {
            Db::attach();
        } else if ('profiler' === $dbLogDriver) {
            Profiler::attach();
        }

        foreach (\config('app.includes', []) as $f) {
            include_once $f;
        }
    }

    public static function addWebServices(FactoryDefault $di): void
    {
        /**
         * @link https://docs.phalcon.io/5.0/en/session
         */
        $di->setShared('session', function () {
            $cc = \config('session')->toArray();
            // https://stackoverflow.com/questions/8311320/how-to-change-the-session-timeout-in-php
            $sessionConfig = $cc['stores'][$cc['driver']];
            switch ($cc['driver']) {
                case 'stream':
                    $adapter = new \Phalcon\Session\Adapter\Stream($sessionConfig);
                    break;
                case 'memcached':
                    $serializerFactory = new \Phalcon\Storage\SerializerFactory();
                    $factory = new \Phalcon\Storage\AdapterFactory($serializerFactory);
                    $adapter = new \Phalcon\Session\Adapter\Libmemcached($factory, $sessionConfig);
                    break;
                case 'redis':
                    $serializerFactory = new \Phalcon\Storage\SerializerFactory();
                    $factory = new \Phalcon\Storage\AdapterFactory($serializerFactory);
                    $adapter = new \Phalcon\Session\Adapter\Redis($factory, $sessionConfig);
                    break;
                case 'noop':
                    $adapter = new \Phalcon\Session\Adapter\Noop();
                    break;
                default:
                    throw new \Exception('un support session driver');
            }
            $session = new \Phalcon\Session\Manager();
            $session->setAdapter($adapter);
            if ($cc['auto_start'] || session_status() == PHP_SESSION_NONE) {
                // session_start();
                $lifetime = $sessionConfig['lifetime'] ?? 0;
                if ($lifetime > 0) { // 在 php.ini 中修改
                    ini_set('session.cookie_lifetime', $lifetime);
                    ini_set('session.gc-maxlifetime', $lifetime);
                }
                $session->start();
            }
            return $session;
        });
        // https://docs.phalcon.io/5.0/en/response#cookies
        // https://docs.phalcon.io/latest/response/#encryption
        $di->setShared('cookies', function () {
            $cc = \config('cookie');
            if ($cc['key']) {
                $cookie = new \Phalcon\Http\Response\Cookies(true, md5($cc['key']));
            } else {
                $cookie = new \Phalcon\Http\Response\Cookies();
            }

            return $cookie;
        });

        $di->setShared('url', function () {
            $url = new \Phalcon\Mvc\Url();
            $origin = rtrim(\config('app.url'), '/') . '/';
            $url->setBaseUri($origin);
            return $url;
        });

        $di->setShared('flash', function () {
            $escaper = new \Phalcon\Html\Escaper();
            $driver = '\Phalcon\Flash\\' . \config('flash');
            $flash = new $driver($escaper);
            $flash->setImplicitFlush(false);
            return $flash;
        });

        $di->setShared('router', function () {
            $router = new \Phalcon\Mvc\Router(false);
//            dd(__FILE__,'默认路由', $router->getRoutes());
            $router->removeExtraSlashes(true);
            return $router;
        });
        $di->setShared('volt', function ($view) use ($di) {
            $volt = new \Phalcon\Mvc\View\Engine\Volt($view, $di);
            // https://docs.phalcon.io/5.0/en/volt#activation
            // https://docs.phalcon.io/5.0/en/volt#functions-1
            $volt->setOptions([
//                    'always'    => true,
                'extension' => '.php',
                'separator' => '_',
//                    'stat'      => true,
                'path' => \config('view.pathDir'),
//                    'prefix'    => '-prefix-',
            ]);
            return $volt;
        });
        // 路由事件
        // https://docs.phalcon.io/5.0/en/views#php
        $di->setShared('view', function () {
            $view = new \Phalcon\Mvc\View();
            $view->registerEngines([
                ".phtml" => \Phalcon\Mvc\View\Engine\Php::class,
                '.volt' => 'volt'
            ]);
            return $view;
        });

        $di->setShared('application', function () {
            return new \Phalcon\Mvc\Application();
        });
    }

    public function runWeb()
    {
        require_once PATH_ROOT . 'routes/web.php';
        Router::start();
//        ddRouterMatch();
        $application = application();
        $application->setDI(self::di());
        $finished = false;
        try {
            // 因为路由 pattern 中去掉了语言，所以这里也需要去掉
            $response = $application->handle(Router::filterIfLanguage($_SERVER['REQUEST_URI']));
            if ($response->isSent()) {
                echo $response->getContent();
            } else {
                return $response->send();
            }
        } catch (BlankException $e) {
            echo $e->getMessage();
        } catch (\Exception $e) {
            if ($finished) {
                echo 'Sorry Dispatch Loop ...', PHP_EOL;
                return null;
            }
            if (IS_DEBUG) {
                Logger::exception($e);
            } elseif (!in_array($e->getCode(), [0, 200])) { // 200 的错误码不记录，通常是请求参数错误
                Logger::exception($e);
            }

            $finished = true;
            if (!($errClass = Response::$redirectResponseClass)) {
                $errClass = \config('app.error');
            }

            if (class_exists($errClass)) {
                if ($e instanceof \Phalcon\Mvc\Dispatcher\Exception) {
                    try {
                        call_user_func_array([
                            $errClass, 'notFound'
                        ], [$e]);
                    } catch (\Exception $e) {
                        echo $e->getMessage();
                    }
                } else {
                    try {
                        call_user_func_array([
                            $errClass, 'exception',
                        ], [$e]);
                    } catch (\Exception $e) {
                        echo $e->getMessage();
                    }
                }
                return null;
            } else {
                appExit('could not find the error handle class');
            }
        }
    }

    /**
     * 具体使用示例，请查看 artisan 文件
     * @return void
     * @throws \Exception
     */
    public function runCLI(): void
    {
        global $argv, $argc;
        $console = application();
        $console->setDI(self::di());

        include_once PATH_ROOT . 'routes/cli.php';

        if ($argc < 2 || in_array($argv[1], ['help', '-help', '--help'])) {
            $outputs = [
                '|<-- examples',
                'php artisan main                  # run task in app/Console/MainTask.indexAction',
                'php artisan main/demo 15          # run task in app/Console/MainTask.demoAction, params is 15',
                'php artisan main -p               # run the default project task',
                'php artisan m/module/task/action  # run the modules task'
            ];
            echo join(PHP_EOL, $outputs), PHP_EOL;
            return;
        }

        if (Router::runCLI($argv[1])) {
            return;
        }
        $options = [
            'm' => Router::ModulePrefix,
            'project' => ''
        ];

        if (in_array('-p', $argv)) {
            $options['project'] = Config::currentProject();
        }
        $arguments = [];
        foreach ($argv as $k => $arg) {
            if ($k === 1) {

                $info = Router::analysisWithCLI($arg, $options);
                if (isset($info['modules'])) {
                    $console->registerModules($info['modules']);
                }
                $arguments = array_merge($arguments, $info);
            } elseif ($k >= 2 && str_starts_with($arg, '-')) {
                $arguments['params'][] = $arg;
            }
        }
        dispatcher()->setDefaultNamespace($arguments['namespace']);


        try {
            $console->handle($arguments);
        } catch (\Phalcon\Cli\Console\Exception $e) {
            fwrite(STDERR, $e->getMessage() . PHP_EOL);
            exit(1);
        } catch (\Throwable $throwable) { // parent of \Exception
            fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
            exit(1);
        }
    }
}