<?php

namespace App\Modules\tao\A0\open\Models;

use App\Modules\tao\BaseModel;

class OpenUserUnionid extends BaseModel
{
    public int $platform = 0; // 平台
    public string $appid = '';
    public string $unionid = '';
    public int $user_id = 0;
}