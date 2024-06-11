<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseModel;

/**
 * 节点表（权限表 permissions）
 */
class SystemNode extends BaseModel
{

    const KIND_PROJECT = 1; // projects
    const KIND_MODULE = 2; // modules

    const TYPE_MODULE = 1;
    const TYPE_CONTROLLER = 2;
    const TYPE_ACTION = 3;

    const AC_INSERT = 1;
    const AC_UPDATE = 2;

    protected string|bool $autoWriteTimestamp = false;

    public int $kind = 0; // 1: projects; 2:modules
    public int $type = 0; // 1: 模块; 2：控制器；3：节点

    public string $module = ''; // 所属模块或项目名称
    public string $node = '';
    public string $title = '';
    public int $ac = 0; // 相比较上一次分析：1 新增；2 更新
    public int $is_auth = 1; // 是否启用 RBAC 权限

    public function tableTitle(): string
    {
        return '节点';
    }
}