<?php

namespace App\Modules\tao\A0\App\Models;

use App\Modules\tao\BaseModel;

class AppFeedback extends BaseModel
{
    public int $user_id = 0;
    public int $info_id = 0; // 应用 ID
    public int $kind = 0;
    public int $status = 1;
    public string $contact = '';
    public string $content = '';
    public string $images = '';
    public string $device = '';
    public string $ip = '';
}