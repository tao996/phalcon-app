<?php
//phpinfo();exit;
define('PATH_ROOT', dirname(__DIR__) . '/');

try {
    // 此时还没有处理到异常
    require PATH_ROOT . 'tao996/index.php';
    $app = require PATH_ROOT . 'bootstrap/app.php';
} catch (\Exception $e) {
    echo $e->getMessage();
    die();
}
/**
 * @var $app \Phax\Foundation\Application
 */
$app->runWeb();