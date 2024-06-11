<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Config\Config;
use App\Modules\tao\Models\SystemNode;
use App\Modules\tao\Models\SystemRole;
use App\Modules\tao\Models\SystemRoleNode;
use App\Modules\tao\Models\SystemUser;
use Phax\Traits\Singleton;
use Phax\Utils\MyHelper;

/**
 * 用户权限验证服务
 */
class UserAuthService
{
    use Singleton;

    private array|null $nodeList = null;
    private SystemUser|null $user = null;
    private array $superAdminId = [];

    private function __construct(SystemUser $user = null)
    {
        if ($user) {
            $this->setUser($user);
        }
        $this->superAdminId = Config::superAdminIds();
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
        if (empty($node)) {
            throw new \Exception('待检查的节点不能为空');
        }

        if (in_array($this->user->id, $this->superAdminId)) {
            return true;
        }

        return in_array($node, $this->getNodeList());
    }

    /**
     * 当前用户角色所能访问的节点列表
     * @return array ['ca1', 'ca2', 'ca3', ...]
     */
    public function getNodeList(): array
    {
        if (is_null($this->nodeList)) {
            $this->nodeList = NodeService::findByRoleIds($this->user->role_ids);
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


}