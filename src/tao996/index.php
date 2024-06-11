<?php
if (!defined('PATH_ROOT')) {
    die('you should define the PATH_ROOT first.');
}
// remove after the package publish release
// 所有 PATH_XXX 都需要以 / 结尾
const PATH_APP = PATH_ROOT . 'app/';
const PATH_PUBLIC = PATH_ROOT . 'public/';
const PATH_STORAGE = PATH_ROOT . 'storage/';
const PATH_STORAGE_DATA = PATH_STORAGE . 'data/';
const PATH_APP_MODULES = PATH_ROOT . 'app/Modules/';
const PATH_APP_PROJECTS = PATH_ROOT . 'app/Http/Projects/';


// 扩展类库
const PATH_PHAX = __DIR__ . '/phax/';

if (file_exists(PATH_ROOT . 'vendor/autoload.php')) {
    require PATH_ROOT . 'vendor/autoload.php';
}

$loader = new \Phalcon\Autoload\Loader();
function loader(): \Phalcon\Autoload\Loader
{
    global $loader;
    return $loader;
}

$loader->setFiles([
    PATH_PHAX . 'Foundation/function.php',
    __DIR__ . '/phar/dotenv.phar',
], true);

$loader->setNamespaces([
    'App' => PATH_APP,
    'Phax' => PATH_PHAX,
], true);

$loader->register();