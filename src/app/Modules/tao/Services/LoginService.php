<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\Services\Auth\LoginAppAuth;
use App\Modules\tao\Services\Auth\LoginAuth;
use App\Modules\tao\Services\Auth\LoginCookieAuth;

class LoginService
{

    /**
     * @var LoginAuth 指定验证方式
     */
    private static LoginAuth|null $authAdapter = null;

    /**
     * @param LoginAuth $authAdapter
     * @return void
     */
    public static function setAuthAdapter(LoginAuth $authAdapter): void
    {
        self::$authAdapter = $authAdapter;
    }

    /**
     * 获取认证适配器
     * @return LoginAuth
     */
    public static function getAuthAdapter(): LoginAuth
    {
        if (is_null(self::$authAdapter)) {
            if (LoginAppAuth::hasLoginKey()) {
                self::$authAdapter = new LoginAppAuth();
            } else {
                self::$authAdapter = new LoginCookieAuth();
            }
        }
        return self::$authAdapter;
    }

    /**
     * 设置登录信息
     * @param SystemUser $user
     * @return mixed 登录标识（可选）
     * @throws \Exception
     */
    public static function makeLogin(SystemUser $user): mixed
    {
        return self::getAuthAdapter()->saveUser($user->toArray());
    }

    /**
     * 检查是否登录
     * @return bool
     */
    public static function tryLogin(): bool
    {
        return LoginUser::getInstance()->isLogin();
    }

    /**
     * 获取登录的用户
     * @return SystemUser
     * @throws \Exception
     */
    public static function getLoginUser(): SystemUser
    {
        return LoginUser::getInstance()->user();
    }
}