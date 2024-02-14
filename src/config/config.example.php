<?php

/**
 * https://github.com/phalcon/cphalcon/issues/16244
 * 线上时，可以直接移除没用的配置信息
 * 以下配置中，使用多个 ## 来表示重要需要修改（通常是密钥）
 */

return [
    'app' => [
        'demo' => true, // 当前是否为演示系统
        // 如果设置 project, 则会读取 config/projectName.php 配置文件
        // 并且前端默认目录会从 app/Http/[Controller|A0|views] 转为 app/Http/Projects/projectName/[Controller|A0|views]
        'project' => env('APP_PROJECT', ''),
        'name' => env('APP_NAME', 'Phax Admin'), // 应用标题/名称
        'url' => env('APP_URL', 'http://localhost/'), // 必须以 / 结尾
        'logo' => env('APP_LOGO', '/assets/logo.png'), // 30*30
        'timezone' => env('TZ', 'UTC'),
        'locale' => 'cn', // 默认的语言（总是2位）[a-z]{2}
        // #######
        'secretKey' => env('APP_SECRET_KEY', 'phaxAdmin'), // 此 key 可能会用来加密系统各种敏感数据（如手机号/邮箱等，一经设定请忽修改）
        'jwt' => [
            'hmac' => 'sha256',
            // #######
            'secret' => env('APP_JWT_SECRET', 'phalcon'),
            'expire' => intval(env('APP_JWT_SECRET', 3600 * 48)),
        ],
        // 异常和错误处理的类
        'error' => 'app\Http\Response',
        'cdn' => 'cn', // cn|ncn|(your cdn address), 本地开发时，可克隆 phalcon-admin-assets，然后将 app.cdn 设置为空
        'namespaces' => [], // name => path
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
                'host' => 'redis',
                'port' => 6379,
                'auth' => env('REDIS_PASSWORD'),
                'index' => 1,
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
                'port' => 3306,// (int)env('DB_PORT', 3306),
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
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => (int)env('DB_PORT', 8432),
                'dbname' => env('DB_DATABASE', 'forge'),
                'username' => env('DB_USERNAME', 'forge'),
                'password' => env('DB_PASSWORD', ''),
                'schema' => env('DB_SCHEMA', 'public')
            ],
            'sqlite' => [
                // https://www.php.net/manual/en/ref.pdo-sqlite.connection.php
                'dbname' => env('DB_DATABASE', '/var/www/database.db'),
            ]
        ],
        // 是否记录 SQL 语句
        'log' => [
            'driver' => env('SQL_LOG', ''), // file|profile，如果为空则表示不开启
            'path' => PATH_STORAGE . (IS_DEBUG ? 'logs/sql-{Ym}.log' : 'logs/sql-{Ymd}.log')
        ],
    ],
    'redis' => [
        'host' => 'redis',
        'port' => 6379,
        'auth' => env('REDIS_PASSWORD'),
        'index' => 0,
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
                'path' => PATH_STORAGE . (IS_DEBUG ? 'logs/app-{Ym}.log' : 'logs/app-{Ymd}.log'),
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
                'host' => 'redis',
                'port' => 6379,
                'auth' => env('REDIS_PASSWORD'),
                'index' => 0,
                'prefix' => env('REDIS_PREFIX', '_ses_'),
                'username' => env('REDIS_USERNAME'),
                'persistent' => env('REDIS_PERSISTENT', false),
// https://github.com/phalcon/cphalcon/blob/5.0.x/phalcon/Storage/Adapter/AbstractAdapter.zep
                'lifetime' => 60 * 60 * 48, // 测试，默認為 3600
            ],
        ],
    ],
    // https://docs.phalcon.io/5.0/en/response#cookies
    'cookie' => [
        'key' => env('CRYPT_KEY', ''), // 加密密钥
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
                'host' => 'redis',
                'port' => 6379,
                'auth' => env('REDIS_PASSWORD'),
                'index' => 1,
                'lifetime' => 86400,
                'prefix' => env('REDIS_PREFIX', '_phm_'),
            ]
        ]
    ]
];