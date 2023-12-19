<?php

use \Phax\Foundation\Router;

// php artisan test 运行测试
Router::addCLI('test', 'php phpunit.phar -c ./phpunit.xml');

// refresh meta-data in order to regenerate it
Router::addCLI('metadata', function () {
    echo "refresh metadata", PHP_EOL;
    metadata()->reset();
});
