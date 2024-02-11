<?php

$app = new \Phax\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? PATH_ROOT
);
$app->autoloadServices();
return $app;