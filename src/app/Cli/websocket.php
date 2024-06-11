<?php
require_once __DIR__ . '/common.php';
// 加载服务
if (!defined('BusinessWorkerEventHandler')) {
    define('BusinessWorkerEventHandler', 'App\Cli\WebsocketEvents');
}

// [PHPSocket.io](https://www.workerman.net/phpsocket_io)
// [GatewayWorker](https://www.workerman.net/doc/gateway-worker/README.html)
const GatewayWorker = true;
require_once PATH_ROOT . 'tao996/phar/workerman.phar';

//require_once FileMonitor.'Events.php';

use Workerman\Worker;

// 配置
// 日志 https://www.workerman.net/doc/workerman/worker/log-file.html
Worker::$logFile = PATH_ROOT . 'storage/logs/workerman_ws.log';
Worker::$pidFile = PATH_ROOT . 'storage/app/workerman_ws.pid';

Worker::runAll();