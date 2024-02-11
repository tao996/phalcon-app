<?php

namespace app\Modules\demo\Models;

use app\Modules\demo\DemoBaseModel;


class Article extends DemoBaseModel
{

    public $id = 0;
    public $user_id = 0;
    public $title = '';
}