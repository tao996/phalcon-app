<?php
require_once __DIR__ . '/common.php';
require_once PATH_ROOT . 'tao996/phar/workerman.phar';

use Workerman\Worker;
use PHPSocketIO\SocketIO;

// 配置
Worker::$logFile = PATH_ROOT . 'storage/logs/workerman_socket.log';
Worker::$pidFile = PATH_ROOT . 'storage/app/workerman_socket.pid';

// https://www.workerman.net/phpsocket_io
$io = new SocketIO(3120);
$io->on('connection', function ($socket) use ($io) {
    echo "connection success\n";

    $socket->on('say', function ($msg) use ($io, $socket) {
        $socket->emit('say', $msg); // 当前客户
        $io->emit('say', 'someone say ' . $msg); // 所有客户
    });
});

Worker::runAll();