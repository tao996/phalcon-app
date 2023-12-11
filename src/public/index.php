<?php

define('PATH_ROOT', dirname(__DIR__).'/');

require PATH_ROOT . 'tao996/index.php';
$app = require PATH_ROOT . 'bootstrap/app.php';
$app->runWeb();