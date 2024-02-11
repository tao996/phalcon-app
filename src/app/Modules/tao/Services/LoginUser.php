<?php

namespace app\Modules\tao\Services;

use app\Modules\tao\Models\SystemUser;
use app\Modules\tao\Services\Auth\LoginAuth;
use app\Modules\tao\Services\Auth\LoginJwtAuth;
use app\Modules\tao\Services\Auth\LoginSessionAuth;

/**
 * 当前登录的用户信息
 */
class LoginUser
{
    private int $userId = 0;
    protected SystemUser $user;

    protected LoginAuth $loginAuth;

    public static function instance($authType = null): self
    {
        static $obj = null;
        if (is_null($obj)) {
            $obj = new LoginUser($authType);
        }
        return $obj;
    }

    /**
     * @param string|null|bool $sessionStorage session|jwt 授权方式；默认为 null，先检查 jwt 再检查 session；如果为 false 则表示跳过检查
     * @throws \Exception
     */
    protected function __construct(private $sessionStorage = null)
    {
        if (false === $sessionStorage) {
            return;
        }

        if (is_string($this->sessionStorage)) {
            if ('session' == $this->sessionStorage) {
                $this->loginAuth = new LoginSessionAuth();
            } elseif ('jwt' == $this->sessionStorage) {
                $this->loginAuth = new LoginJwtAuth();
            } else {
                throw new \Exception('暂不支持的认证方式:' . $this->sessionStorage);
            }
        } else {
            $jwt = new LoginJwtAuth();
            if ($jwt->hasLoginKey()) {
                $this->sessionStorage = 'jwt';
                $this->loginAuth = $jwt;
            } else {
                $this->sessionStorage = 'session';
                $this->loginAuth = new LoginSessionAuth();
            }
        }
        // 尝试获取用户信息
        if ($user = $this->getLoginAuth()->getUser()) {
            $this->userId = $user->id;
            $this->user = $user;
        }
    }

    private function getLoginAuth(): LoginAuth
    {
        return $this->loginAuth;
    }

    public function isJwt(): bool
    {
        return $this->sessionStorage == 'jwt';
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
                $this->getLoginAuth()->saveUser($user->toArray());
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
        $this->getLoginAuth()->saveUser($info
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
     * 如果用户是 session 登录，则退出登录
     * @return void
     */
    public function logout(): void
    {
        $this->getLoginAuth()->destroy();
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