<?php

namespace App\Modules\tao\Console;

use Phalcon\Cli\Task;

class MigrateTask extends Task
{
    /**
     * docker-compose exec php sh
     * php article m/tao/migrate
     * @return void
     */
    public function indexAction()
    {
        echo 'use "php artisan migrate r --m=tao" replace', PHP_EOL;
    }
}