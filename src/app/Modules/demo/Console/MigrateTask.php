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
        echo "如果你使用 Mysql，那么数据已经在 init/mysql 中导入了", PHP_EOL;
        echo "其它数据库请进入服务后执行 php artisan migrate r --m=demo", PHP_EOL;
    }
}