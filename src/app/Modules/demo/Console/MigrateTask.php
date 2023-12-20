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
        echo "不需要，数据已经在 init/mysql 中导入了", PHP_EOL;
    }
}