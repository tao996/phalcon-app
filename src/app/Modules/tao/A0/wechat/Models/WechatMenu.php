<?php

namespace App\Modules\tao\A0\wechat\Models;

use App\Modules\tao\BaseModel;

class WechatMenu extends BaseModel
{
    protected array $allowEmptyFields = ['content'];

    public int $sync = 1; // 是否开启同步
    public int $sync_at = 0; // 同步更新时间
    public string $appid = ''; // 微信 appId
    public string $content = ''; // 菜单数据

}