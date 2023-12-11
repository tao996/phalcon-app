<?php

use \Phax\Foundation\Router;

// php artisan test 运行测试
Router::addCLI('test', 'php phpunit.phar -c ./phpunit.xml');
Router::addCLI('debug', function () {
    $file = '/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini';
    if (file_exists($file)) {
        system('mv ' . $file . ' ' . $file . '.disabled');
        echo "close xdebug", PHP_EOL;
    } else {
        system('mv ' . $file . '.disabled ' . $file);
        echo "open xdebug", PHP_EOL;
    }
});