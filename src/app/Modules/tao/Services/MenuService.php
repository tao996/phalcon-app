<?php

namespace app\Modules\tao\Services;

use app\Modules\tao\Config\Config;
use app\Modules\tao\Models\SystemMenu;
use app\Modules\tao\Models\SystemUser;
use Phax\Db\ThinkQueryBuilder;

class MenuService
{
    public function __construct(public SystemUser $user)
    {
    }

    /**
     * 获取首页信息
     * @return array
     * @throws \Exception
     */
    public function getHomeInfo(): array
    {
        return SystemMenu::queryBuilder()->columns(['title', 'icon', 'href'])
            ->int('pid', Config::HOME_PID)
            ->findFirst(true, function (&$row) {
                empty($row['href']) ?: $row['href'] = url($row['href']);
            });

    }

    public function getMenuTree(): array
    {
        $nodes = UserAuthService::getInstance($this->user)->getNodeList();
        return $this->buildMenuChild(0, $this->getMenuData(), $nodes);
    }

    public function buildMenuChild(int $pid, array $menuList, array $nodes): array
    {
        $treeList = [];
//dd($menuList);
        foreach ($menuList as $v) {
            if (empty($v['href'])) {
                $check = true;
            } elseif ($v['tag'] == SystemMenu::TAG_USER || $v['tag'] == SystemMenu::TAG_OPEN) {
                $check = true;
            } elseif ($v['tag'] == SystemMenu::TAG_SUPER_ADMIN && in_array($this->user->id, Config::SUPER_ADMIN_IDS)) {
                $check = true;
            } else {
                $check = UserAuthService::accessNode($this->user->id, $nodes, $v['href']);
            }
            if (!empty($v['href'])) {
                if ($v['multi'] == 1) {
                    $v['href'] = url($v['href']); // 转为模块链接
                } elseif (!str_starts_with($v['href'], '/')) {
                    $v['href'] = '/' . $v['href'];
                }
            }


            if ($pid == $v['pid'] && $check) {
                $node = $v;
                $child = $this->buildMenuChild($v['id'], $menuList, $nodes);
                if (!empty($child)) {
                    $node['child'] = $child;
                }
                if (!empty($v['href']) || !empty($child)) {
                    $treeList[] = $node;
                }
            }
        }
//        dd($treeList);
        return $treeList;
    }

    /**
     * 获取全部菜单数据
     * @return array
     */
    protected function getMenuData(): array
    {
        return ThinkQueryBuilder::with(SystemMenu::class)
            ->where('status', 1)
            ->where('pid', '<>', Config::HOME_PID)
            ->softDelete()->order(['sort desc', 'id asc'])
            ->find('id, pid, title, icon, href, multi,tag');
    }
}