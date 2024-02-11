<?php

namespace app\Modules\tao\Models;

use app\Modules\tao\BaseModel;

class SystemRoleNode extends BaseModel
{
    public int $role_id = 0;
    public int $node_id = 0;

    public function tableTitle(): string
    {
        return '角色节点关联表';
    }
}