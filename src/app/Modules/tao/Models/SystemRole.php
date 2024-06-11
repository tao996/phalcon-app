<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseModel;
use Phax\Db\QueryBuilder;
use Phax\Db\ThinkQueryBuilder;
use Phax\Traits\SoftDelete;

class SystemRole extends BaseModel
{
    use SoftDelete;

    public string $name = ''; // 会员名称（特殊情况使用）
    public string $title = '';
    public int $sort = 0;
    public int $status = 1; // 0 禁用，1启用（跟原版不同）
    public string $remark = '';

    public function tableTitle(): string
    {
        return '角色';
    }

    public static function getActiveList()
    {
        return ThinkQueryBuilder::with(SystemRole::class)
            ->softDelete()
            ->where('status', 1)
            ->column('title', 'id');
    }

    public function beforeSave()
    {
        if (empty($this->title)) {
            throw new \Exception('必须填写角色名称');
        }
        if (!empty($this->name)) {
            if (!preg_match('|^\w+$|', $this->name)) {
                throw new \Exception('角色英文名称只支持字母数字下划线');
            }
            if (QueryBuilder::with($this)->string('name', $this->name)
                ->notEqual('id', $this->id, true)
                ->exits()) {
                throw new \Exception('角色英文名称重复');
            }
        }

        if (QueryBuilder::with($this)->string('title', $this->title)
            ->notEqual('id', $this->id, true)
            ->exits()) {
            throw new \Exception('角色名称重复');
        }

    }

    /**
     * 根据角色 ID 获取授权节点
     * @return array
     */
    public function getAuthorizeNodeListByRoleId(): array
    {
        $bindNodes = SystemRoleNode::queryBuilder()
            ->int('role_id', $this->id)->columns('node_id')
            ->find();
        $bindNodeIds = array_column($bindNodes, 'node_id');
// 全部的的节点
        $nodeList = SystemNode::queryBuilder()
            ->int('is_auth', 1)
            ->field('id,node,title,type,is_auth')
            ->find();
        // 重新排
        $newNodeList = [];
        foreach ($nodeList as $vm) {
            // 模块
            if ($vm['type'] == SystemNode::TYPE_MODULE) {
                $vm = array_merge($vm, ['field' => 'node']);
                $vm['title'] = "{$vm['title']}【{$vm['node']}】";
                $vm['children'] = [];
                $hasModuleSpread = false;

                foreach ($nodeList as $vc) {// 控制器
                    if ($vc['type'] == SystemNode::TYPE_CONTROLLER && str_starts_with($vc['node'], $vm['node'])) {
                        $vc = array_merge($vc, ['field' => 'node']);
                        $vc['checked'] = false;
                        $vc['title'] = "{$vc['title']}【{$vc['node']}】";
                        $hasControllerSpread = false;

                        $children = [];
                        foreach ($nodeList as $v) {// 操作
                            if ($v['type'] == SystemNode::TYPE_ACTION && str_starts_with($v['node'], $vc['node'])) {
                                $v = array_merge($v, ['field' => 'node', 'spread' => false]);
                                $v['checked'] = in_array($v['id'], $bindNodeIds);
                                if ($v['checked']) {
                                    $hasModuleSpread = true;
                                    $hasControllerSpread = true;
                                }
                                $v['title'] = "{$v['title']}【{$v['node']}】";
                                $children[] = $v;
                            }
                        }
                        $vc['children'] = $children ?: [];
                        $vc['spread'] = $hasControllerSpread;
                        $vm['children'][] = $vc;
                    }
                }
                $vm['spread'] = $hasModuleSpread;
                $newNodeList[] = $vm;
            }
        }
        return $newNodeList;
    }

}