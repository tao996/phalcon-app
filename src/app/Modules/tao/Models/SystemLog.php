<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseModel;

class SystemLog extends BaseModel
{
    protected string $updatedTime = ''; // 不需要更新时间

    public int $user_id = 0;
    public string $url = '';
    public string $method = '';
    public string $action = ''; // 操作方法
    public string $title = '';
    public string $ip = '';
    public string $useragent = '';

    public function user()
    {
        return $this->hasOnePhx(SystemUser::class);
    }

    public function tableTitle(): string
    {
        return '日志';
    }
}