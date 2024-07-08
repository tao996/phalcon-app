<?php

/**
 * https://github.com/phalcon/cphalcon/issues/16244
 * 线上时，可以直接移除没用的配置信息
 */

return [
    'app' => [
        'demo' => true, // 当前是否为演示系统
        'name' => env('APP_NAME', 'Phax Admin'), // 应用标题/名称
        'url' => env('APP_URL', 'http://localhost/'), // 必须以 / 结尾
        'logo' => env('APP_LOGO', '/assets/logo.png'), // 30*30
        'timezone' => env('TZ', 'UTC'),
        'locale' => 'cn', // 默认的语言（总是2位）[a-z]{2}
        'jwt' => [
            'hmac' => 'sha256',
            'secret' => env('APP_JWT_SECRET', 'phalcon'), // 必须修改
            'expire' => intval(env('APP_JWT_SECRET', 3600 * 48)),
        ],
        // 异常和错误处理的类
        'error' => 'App\Http\Response',
        // cn|ncn|(your cdn domain); 本地开发时，可不填，则从 src/public/assets 中读取
        'cdn' => 'cn',
        'namespaces' => [], // name => path
        'includes' => [], // 需要包含的文件
        'project' => [
            'sites' => [], // '项目'=>'域名' 如 'city' => ['phax.test']
            'default' => '', // 默认的项目
            'config_prefix' => false // 是否以项目名称作为配置文件前辍，默认为 config.php; 如果为 true，则加载 项目名.config.php
        ],
        /**
         * 超级管理员用户 ID 列表，不受权限控制；
         * 注意：写在前面的 user_id 可以修改写在后面的 user_id 的记录；
         * 比如 [1,2] 同样的超级管理员；但 1 可以修改 2 的记录，2 不能修改 1 的记录；
         * 如果是 [2,1] 是 2 可以修改 1 的记录，1 不能修改 2 的记录
         */
        'superAdmin' => [1], // 超级管理员账号 ID
    ],
    // https://docs.phalcon.io/5.0/en/cache
    'cache' => [
        'driver' => env('CACHE_DRIVER', 'redis'), // apcu, memcached, memory, redis, stream
        'stores' => [
            'apcu' => [
                'defaultSerializer' => 'Json',
                'lifetime' => 7200
            ],
            'redis' => [
                'defaultSerializer' => 'Json',
                'lifetime' => 7200,
                'host' => env('REDIS_HOST', 'redis'),
                'port' => (int)env('REDIS_PORT', 6379),
                'auth' => env('REDIS_PASSWORD'),
                'index' => env('REDIS_CACHE_INDEX', 0),
                'prefix' => env('CACHE_PREFIX', '_phc_'),
                'persistent' => env('CACHE_PERSISTENT', false)
            ],
            'stream' => [
                'defaultSerializer' => 'Json',
                'lifetime' => 7200,
                'prefix' => env('CACHE_PREFIX', '_phc_'),
                'storageDir' => PATH_STORAGE . 'cache'
            ],
            'memory' => [ // warning: https://docs.phalcon.io/5.0/en/cache#memory
                'defaultSerializer' => 'Json',
                'lifetime' => 7200,
                'prefix' => env('CACHE_PREFIX', '_phc_')
            ],
            'memcached' => [
                'defaultSerializer' => 'Json',
                'lifetime' => 3600,
                'prefix' => env('CACHE_PREFIX', '_phc_'),
                'saslAuthData' => [
                    'user' => env('MEMCACHED_USER'),
                    'pass' => env("MEMCACHED_PASS"),
                ],
                'servers' => [
                    0 => [
                        'host' => env('MEMCACHED_HOST', 'memcached'),
                        'port' => (int)env('MEMCACHED_PORT', 11211),
                        'weight' => 1,
                    ],
                ],
            ]
        ],
    ],
    'view' => [
        'pathDir' => PATH_STORAGE . 'cache/view', // volt 模板缓存位置
    ],
    'database' => [
        'default' => 'mysql',// env('DB_CONNECTION', 'mysql'),
        'stores' => [
            'mysql' => [
                'host' => env('MYSQL_HOST', '127.0.0.1'),
                'port' => (int)env('MYSQL_PORT', 3306),
                'dbname' => env('MYSQL_DATABASE', 'forge'),
                'username' => env('MYSQL_USER', 'forge'),
                'password' => env('MYSQL_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'options' => [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ],
            ],
            'postgresql' => [
                'host' => env('POSTGRES_HOST', 'postgres'),
                'port' => (int)env('POSTGRES_PORT', 5432),
                'dbname' => env('POSTGRES_DB', 'forge'),
                'username' => env('POSTGRES_USER', 'forge'),
                'password' => env('POSTGRES_PASSWORD', ''),
                'schema' => env('POSTGRES_SCHEMA', 'public')
            ],
            'sqlite' => [
                // https://www.php.net/manual/en/ref.pdo-sqlite.connection.php
                'dbname' => env('DB_DATABASE', '/var/www/database.db'),
            ]
        ],
        // 是否记录 SQL 语句
        'log' => [
            'driver' => env('SQL_LOG', ''), // file|profile，如果为空则表示不开启
            'path' => PATH_STORAGE . (IS_DEBUG ? 'logs/sql_{Ym}.log' : 'logs/sql_{Ymd}.log')
        ],
    ],
    'redis' => [
        'host' => env('REDIS_HOST', 'redis'),
        'port' => env('REDIS_PORT', 6379),
        'auth' => env('REDIS_PASSWORD'),
        'index' => (int)env('REDIS_CACHE_INDEX', 0),
        'prefix' => env('REDIS_PREFIX', '_phx_'),
        'username' => env('REDIS_USERNAME'),
        'persistent' => env('REDIS_PERSISTENT', false),
    ],
    'crypt' => [
        'key' => env('CRYPT_KEY', 'phax'), // ### 加密密钥
        'padding' => env('CRYPT_PADDING', ''),
        'cipher' => env('CRYPT_CIPHER', 'aes-256-cfb'),
    ],
    // https://docs.phalcon.io/5.0/en/logger
    'logger' => [
        'driver' => env('LOGGER_DRIVER', 'stream'), // stream, syslog, noop
        'stores' => [
            'stream' => [
                'path' => PATH_STORAGE . (IS_DEBUG ? 'logs/app_{Ym}.log' : 'logs/app_{Ymd}.log'),
                'name' => env('LOG_NAME', 'main'),
                'level' => 'message',
            ],
            'syslog' => [
                'ident' => env('SYSLOG_IDENT', 'ident-name'),
                'level' => env('LOG_LEVEL', 'message'),
                'name' => env('LOG_NAME', 'main'),
            ],
            'noop' => [],
        ]
    ],
    // https://docs.phalcon.io/5.0/en/session
    'session' => [
        'auto_start' => true,
        'driver' => 'redis', // stream, memcached, redis, noop(just for test),
        'stores' => [
            'stream' => [
                'savePath' => PATH_STORAGE . 'cache/session',
            ],
            'memcached' => [
                'client' => [],
                'servers' => [
                    [
                        'host' => env('MEMCACHED_HOST', 'memcached'),
                        'port' => (int)env('MEMCACHED_PORT', 11211),
                        'weight' => 0,
                    ],
                ],
            ],
            'redis' => [
                'host' => env('REDIS_HOST', 'redis'),
                'port' => (int)env('REDIS_PORT', 6379),
                'auth' => env('REDIS_PASSWORD'),
                'index' => (int)env('REDIS_SESSION_INDEX', 0),
                'prefix' => env('REDIS_PREFIX', '_ses_'),
                'username' => env('REDIS_USERNAME'),
                'persistent' => env('REDIS_PERSISTENT', false),
// https://github.com/phalcon/cphalcon/blob/5.0.x/phalcon/Storage/Adapter/AbstractAdapter.zep
                'lifetime' => 3600, // 测试，默認為 3600
            ],
        ],
    ],
    // https://docs.phalcon.io/5.0/en/response#cookies
    'cookie' => [
        'key' => env('CRYPT_KEY', 'phalconX'), // 加密密钥
        'secret' => false,
        'domain' => null,
    ],
    'flash' => 'direct',
    // https://docs.phalcon.io/5.0/en/db-models-metadata
    'metadata' => [
        // use memory in dev
        // apcu|redis|stream|memory(测试)
        'driver' => env('METADATA_DRIVER', 'redis'),
        'stores' => [
            'stream' => [
                'metaDataDir' => PATH_STORAGE . 'cache',
            ],
            'apcu' => [
                'lifetime' => 86400,
                'prefix' => '_phm_',
            ],
            'memcached' => [
                'servers' => [
                    0 => [
                        'host' => env('MEMCACHED_HOST', 'memcached'),
                        'port' => (int)env('MEMCACHED_PORT', 11211),
                        'weight' => 1,
                    ],
                ],
                'lifetime' => 86400,
                'prefix' => '_phm_',
            ],
            'redis' => [
                'host' => env('REDIS_HOST', 'redis'),
                'port' => (int)env('REDIS_PORT', 6379),
                'auth' => env('REDIS_PASSWORD'),
                'index' => 1,
                'lifetime' => 86400,
                'prefix' => env('REDIS_PREFIX', '_phm_'),
            ]
        ]
    ]
];