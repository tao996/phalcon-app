<?php

namespace app\Modules\tao\A0\wechat\Models;

use app\Modules\tao\BaseModel;

class WechatUserUnionid extends BaseModel
{

    public string $app_id = ''; // 微信 appId
    public string $unionid = ''; // 用户标识符 (unique)
    public int $user_id = 0; // 关联用户 id
}