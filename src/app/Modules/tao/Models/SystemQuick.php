<?php

namespace app\Modules\tao\Models;

use app\Modules\tao\BaseModel;
use app\Modules\tao\Config\Config;
use Phax\Traits\SoftDelete;

class SystemQuick extends BaseModel
{
    use SoftDelete;

    public int $user_id = 0;
    public string $title = '';
    public string $icon = '';
    public string $href = '';
    public int $sort = 0;
    public int $status = Config::STATUS_ACTIVE;
    public string $remark = '';

    public function tableTitle(): string
    {
        return '快捷菜单';
    }
}