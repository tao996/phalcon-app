<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Config\Config;
use App\Modules\tao\Config\Data;
use App\Modules\tao\Models\SystemMenu;
use App\Modules\tao\Models\SystemUser;

class UserMenuService
{
    private array $superAdminIds = [];

    public function __construct(public SystemUser $user)
    {
        $this->superAdminIds = Config::superAdminIds();
    }

    /**
     * 获取首页信息
     * @return array
     * @throws \Exception
     */
    public function getHomeInfo(): array
    {
        return SystemMenu::queryBuilder()->columns(['title', 'icon', 'href', 'type', 'params'])
            ->int('pid', Data::HOME_PID)
            ->findFirst(true, function (&$row) {
                if ($row['href']) {
                    $row['href'] = MenuService::href($row['href'], $row['type'], $row['params']);
                }
            });

    }

    public function getMenuTree(): array
    {
        // 节点的数据来自 tao_system_node
        $userNodes = UserAuthService::getInstance($this->user)->getNodeList();
        // 系统菜单来自用户自定义,可能带有 /m/, /p/
        $systemMenus = SystemMenu::queryBuilder()
            ->int('status', 1)
            ->notEqual('pid', Data::HOME_PID)
            ->order('sort desc, id asc')
            ->findColumn('id, pid, title, icon, href, type, roles,params');
        $menus = $this->buildMenuChild(0, $systemMenus, $userNodes, '');
        // 过滤掉空节点的一级菜单
        return array_values(array_filter($menus, function ($menu) {
            return $menu['pid'] == 0 && !empty($menu['child']);
        }));
    }


    private function buildMenuChild(int $pid, array $menuList, array $nodes, string $defRole = ''): array
    {
        $treeList = [];
        foreach ($menuList as $v) {
            if ($pid != $v['pid']) {
                continue;
            }
            $check = false;
            while (true) {
                if (in_array($this->user->id, $this->superAdminIds)) {
                    $check = true;
                    break;
                }
                if (empty($v['roles'])) {
                    $v['roles'] = $defRole;
                }
                if ($v['roles']) {
                    if (Data::AccessUser == $v['roles']) {
                        $check = true;
                    }
                    break;
                }

                $check = empty($v['href']) || in_array($v['href'], $nodes);
                break;
            }

            if (!$check) {
                continue;
            }
            $v['href'] = MenuService::href($v['href'], $v['type'], $v['params']);

            $node = $v;
            $node['child'] = $this->buildMenuChild($v['id'], $menuList, $nodes, $v['roles']);
            $treeList[] = $node;

        }
        return $treeList;
    }

}