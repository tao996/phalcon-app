<?php

namespace app\Modules\tao\Models;

use app\Modules\tao\BaseModel;

class SystemNode extends BaseModel
{
    const TYPE_MODULE = 1;
    const TYPE_CONTROLLER = 2;
    const TYPE_ACTION = 3;

    const AC_INSERT = 1;
    const AC_UPDATE = 2;

    protected string|bool $autoWriteTimestamp = false;

    public string $module = '';
    public string $node = '';
    public string $title = '';
    public int $type = 0; // 1: 模块; 2：控制器；3：节点
    public int $ac = 0; // 相比较上一次分析：1 新增；2 更新
    public int $is_auth = 1; // 是否启用 RBAC 权限

    public function tableTitle(): string
    {
        return '节点';
    }
}