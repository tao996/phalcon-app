<?php

if (str_starts_with(strtolower(PHP_OS), 'win')) {
    exit("start.php not support windows, please use start_for_win.bat\n");
}

// 检查扩展
if (!extension_loaded('pcntl')) {
    exit("Please install pcntl extension.\n");
}

if (!extension_loaded('posix')) {
    exit("Please install posix extension.\n");
}
// 标记是全局启动
const GLOBAL_START = 1;
const DISABLE_EXIT = true; // 禁用 die/exit

define('PATH_ROOT', dirname(__DIR__, 2) . '/');

try {
    // 此时还没有处理到异常
    require PATH_ROOT . 'tao996/index.php';
    $app = require PATH_ROOT . 'bootstrap/app.php';
} catch (\Exception $e) {
    echo $e->getMessage();
    die();
}
// 监听目录（DEBUG 模式下）
const FileMonitor = __DIR__;