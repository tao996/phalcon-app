<?php

namespace app\Modules\tao\Models;

use app\Modules\tao\BaseModel;

class SystemMigration extends BaseModel
{

    public string $version = ''; // 唯一
    public string $summary = ''; // 更新的内容
}