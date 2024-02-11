<?php

namespace app\Modules\demo;

use Phax\Mvc\Model;

class DemoBaseModel extends Model
{
    protected string $tablePrefix = 'demo_';
}