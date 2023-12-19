<?php

namespace App\Modules\demo\Console;

class MigrateTask
{
    /**
     * php artisan m/demo/migrate
     * @return void
     */
    public function indexAction()
    {
        $name = 'demo_article';
        if (db()->tableExists($name)) {
            echo "demo table exists, skip init demo Module db", PHP_EOL;
        } else {
            $sql = file_get_contents(dirname(__DIR__) . '/data/demo.sql');
            pdo()->exec($sql);
            if (db()->tableExists($name)) {
                echo "import demo Module data success", PHP_EOL;
            } else {
                echo "import demo Module data failed", PHP_EOL;
            }
        }
    }
}