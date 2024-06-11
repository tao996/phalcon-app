<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseModel;

class SystemRoleNode extends BaseModel
{
    public int $role_id = 0;
    public int $node_id = 0;

    public function tableTitle(): string
    {
        return '角色节点关联表';
    }
}