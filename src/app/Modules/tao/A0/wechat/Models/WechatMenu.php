<?php

namespace app\Modules\tao\A0\wechat\Models;

use app\Modules\tao\BaseModel;
use app\Modules\tao\Config\Config;

class WechatMenu extends BaseModel
{
    protected array $allowEmptyFields = ['content'];

    public int $sync = Config::STATUS_ACTIVE; // 是否开启同步
    public int $sync_at = 0; // 同步更新时间
    public string $app_id = ''; // 微信 appId
    public string $content = ''; // 菜单数据

}