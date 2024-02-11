<?php

namespace app\Modules\tao\Services;

use app\Modules\tao\Config\Config;
use app\Modules\tao\Models\SystemNode;
use app\Modules\tao\Models\SystemRole;
use app\Modules\tao\Models\SystemRoleNode;
use app\Modules\tao\Models\SystemUser;
use Phax\Support\Facades\Helper;
use Phax\Traits\Singleton;

/**
 * 用户权限验证服务
 */
class UserAuthService
{
    use Singleton;

    private array|null $nodeList = null;
    private SystemUser $user;

    private function __construct(SystemUser $user = null)
    {
        if ($user) {
            $this->setUser($user);
        }
    }

    /**
     * 设置当前用户
     * @param SystemUser|null $user
     * @return $this
     * @throws \Exception
     */
    public function setUser(SystemUser|null $user): static
    {
        if (empty($user) || $user->id < 1) {
            throw new \Exception('必须指定用户 setUser');
        }
        $this->user = $user;
        return $this;
    }

    /**
     * 当前用户能否访问指定的节点
     * @param string $node 待检查的节点
     * @return bool
     * @throws \Exception
     */
    public function access(string $node): bool
    {
        return self::accessNode($this->user->id, $this->getNodeList(), $node);
    }

    /**
     * 当前用户角色所能访问的节点列表
     * @return array ['ca1', 'ca2', 'ca3', ...]
     */
    public function getNodeList(): array
    {
        if (is_null($this->nodeList)) {
            $this->nodeList = self::getUserNode($this->user->role_ids);
        }
        return $this->nodeList ?: [];
    }

    /**
     * 是否在指定角色中
     * @param array $roles 支持字符串（角色名）数组或角色ID
     * @return bool
     * @throws \Exception
     */
    public function inRoles(array $roles): bool
    {
        if (empty($roles)) {
            throw new \Exception('待检查的角色不能为空');
        }
        if (!is_integer(end($roles))) {
            $roles = RoleService::getIds($roles);
        }
        return !empty(array_intersect($this->user->roleIds(), $roles));
    }

    /**
     * 获取指定角色的可访问节点
     * @param string $role_ids 用户的角色 ID
     * @return array ['ca1', 'ca2', 'ca3', ...]
     */
    public static function getUserNode(string $role_ids): array
    {
        $nodeList = [];
        if (!empty($role_ids)) {
            $authSQL = 'SELECT id FROM ' . SystemRole::getObject()->getSource() . ' WHERE id IN (' . $role_ids . ')';
            $authNodeSQL = 'SELECT node_id FROM ' . SystemRoleNode::getObject()->getSource() . ' WHERE role_id IN (' . $authSQL . ')';
            $nodeListSQL = 'SELECT node FROM ' . SystemNode::getObject()->getSource() . ' WHERE id IN (' . $authNodeSQL . ')';
            $rows = db()->query($nodeListSQL)->fetchAll(\PDO::FETCH_ASSOC);
            $nodeList = Helper::pluck($rows, 'node');
        }
        return $nodeList;
    }

    /**
     * @param int $userId 用户 ID
     * @param array $nodes 用户所能访问的全部节点
     * @param string $node 待检查的节点
     * @return bool
     */
    public static function accessNode(int $userId, array $nodes, string $node): bool
    {
        if (in_array($userId, Config::SUPER_ADMIN_IDS)) {
            return true;
        }
        if (empty($node)) {
            throw new \Exception('待检查的节点不能为空');
        }
//        dd($node, $nodes);
        return in_array($node, $nodes);
    }
}