<?php

namespace App\Modules\demo\Models;

use App\Modules\demo\DemoBaseModel;

class Profile extends DemoBaseModel
{
    public $id = 0;
    public $user_id = 0;
    public $age = 0;
    public $remark = '';
}