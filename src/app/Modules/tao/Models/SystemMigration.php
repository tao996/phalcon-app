<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseModel;

class SystemMigration extends BaseModel
{

    public string $version = ''; // 唯一
    public string $summary = ''; // 更新的内容
}