<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\Services\Auth\LoginAuth;
use Phax\Traits\Singleton;

/**
 * 当前登录的用户信息
 */
class LoginUser
{
    private int $userId = 0;
    protected SystemUser $user;
    protected LoginAuth $loginAuth;

    use Singleton;

    /**
     * @throws \Exception
     */
    protected function __construct()
    {
        $this->loginAuth = LoginService::getAuthAdapter();
        // 尝试获取用户信息
        try {
            if ($user = $this->loginAuth->getUser()) {
                $this->userId = $user->id;
                $this->user = $user;
            }
        } catch (\Exception $e) {
            if (IS_DEBUG) {
                throw $e;
            }
        }
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function isLogin(): bool
    {
        return $this->userId > 0;
    }

    public function isSuperAdmin(): bool
    {
        return $this->user->isSuperAdmin();
    }

    public function mustSuperAdmin(): void
    {
        if (!$this->isSuperAdmin()) {
            throw new \Exception('非超级管理员无法操作');
        }
    }

    public function mustLogin(): void
    {
        if ($this->userId < 1) {
            throw new \Exception('您还没有登录');
        }
    }

    /**
     * 重新加载用户信息
     * @param int $userId 用户 ID
     * @return void
     * @throws \Exception
     */
    public function loadUserInfo(int $userId): void
    {
        if ($userId > 0) {
            if ($user = SystemUser::findFirst($userId)) {
                $this->loginAuth->saveUser($user->toArray());
                $this->user = $user;
                $this->userId = $user->id;
            } else {
                throw new \Exception('您的账号是否被注销');
            }
        }
    }

    /**
     * 更新用户信息
     * @param array $info
     * @return void
     */
    public function updateUserInfo(array $info = []): void
    {
        $this->loginAuth->saveUser($info
            ? array_merge($this->user->toArray(), $info)
            : $this->user->toArray());
        $this->user->assign($info);
    }

    /**
     * 获取当前登录的用户
     * @throws \Exception
     */
    public function user(): SystemUser
    {
        return $this->user;
    }

    /**
     * web 清除当前用户缓存信息
     * @return void
     * @throws \Exception
     */
    public function clearCache(): void
    {
        // 重新加载用户基本信息
        $this->loadUserInfo($this->userId);
    }

    /**
     * 登录
     * @return void
     */
    public function logout(): void
    {
        $this->loginAuth->destroy();
    }

    /**
     * 获取授权服务
     * @return UserAuthService
     */
    public function getAuth(): UserAuthService
    {
        static $auth = null;
        if (is_null($auth)) {
            $auth = UserAuthService::getInstance($this->user);
        }
        return $auth;
    }
}