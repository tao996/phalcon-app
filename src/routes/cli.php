<?php

use \Phax\Foundation\Router;

// php artisan test 运行测试
Router::addCLI('test', 'php phpunit.phar -c ./phpunit.xml');
