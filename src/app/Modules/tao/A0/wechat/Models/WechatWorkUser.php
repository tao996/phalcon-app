<?php

namespace App\Modules\tao\A0\wechat\Models;

use App\Modules\tao\BaseModel;

class WechatWorkUser extends BaseModel
{

    public string $crop_id = ''; // 企业微信 id
    public string $agent_id = ''; // 企业应用 id
    public string $user = ''; // 企业微信用户
    public int $user_id = 0; // 绑定的用户账号 ID
}