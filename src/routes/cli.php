<?php

use \Phax\Foundation\Router;

// php artisan test 运行测试
Router::addCLI('test', 'php phpunit.phar -c ./phpunit.xml');

// refresh meta-data when you update the model
Router::addCLI('metadata', function () {
    echo "refresh metadata", PHP_EOL;
    metadata()->reset();
});

// run `php artisan migration` to see the help
Router::addCLI('migration', function () {
    include_once PATH_ROOT . 'phalcon-migrations.phar';
//    include_once PATH_ROOT . 'phalcon-migrations/index.php';
    \phalconMigration(function (\Phalcon\Cop\Parser $parser) {
        $argv = empty($_SERVER['argv']) ? [] : $_SERVER['argv'];
        array_shift($argv);
        $parser->parse($argv);
    });
});