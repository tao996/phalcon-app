<?php

namespace App\Modules\demo\Models;

use App\Modules\demo\DemoBaseModel;


class Article extends DemoBaseModel
{

    public $id = 0;
    public $user_id = 0;
    public $title = '';
}