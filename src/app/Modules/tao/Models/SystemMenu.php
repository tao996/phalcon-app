<?php

namespace app\Modules\tao\Models;

use app\Modules\tao\BaseModel;
use app\Modules\tao\Config\Config;
use Phax\Traits\SoftDelete;

class SystemMenu extends BaseModel
{
    const TAG_AUTH = ''; // 默认为空，正常授权
    const TAG_USER = 'user'; // 用户可见
    const TAG_OPEN = 'open'; // 公开（通常用于前台菜单）
    const TAG_SUPER_ADMIN = 'superAdmin'; // 超级管理员可见

    use SoftDelete;

    public int $pid = 0; // 父 ID
    public string $title = ''; // 名称
    public string $icon; // 菜单图标
    public string $href = ''; // 链接地址
    public string $params = ''; // 链接参数（暂时没用到）
    public int $multi = 1; // 多模块，默认为是
    public int $sort = 0; // 菜单排序
    public int $status = Config::STATUS_ACTIVE; // 状态 0 禁用 1 启用
    public string $remark = ''; // 备注信息（通常是关键字）
    public string $tag = ''; // 角色限制


    public function tableTitle(): string
    {
        return '菜单';
    }
}