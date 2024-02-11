<?php

namespace app\Modules\tao\Console;

use Phalcon\Cli\Task;

class MainTask extends Task
{
    public function indexAction()
    {
        echo "Thank you for use", PHP_EOL;
    }
}