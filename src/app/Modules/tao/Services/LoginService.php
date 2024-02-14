<?php

namespace app\Modules\tao\Services;

use app\Modules\tao\Models\SystemUser;
use app\Modules\tao\Services\Auth\LoginAuth;
use app\Modules\tao\Services\Auth\LoginJwtAuth;
use app\Modules\tao\Services\Auth\LoginSessionAuth;

class LoginService
{

    /**
     * @var string 指定验证方式
     */
    public static string $authAdapter = '';

    /**
     * 获取认证适配器
     * @return LoginAuth
     */
    public static function getAuthAdapter(): LoginAuth
    {

        if (self::$authAdapter == 'jwt' || request()->getPost('session') == 'jwt' || request()->hasHeader('Authorization')) {
            self::$authAdapter = 'jwt';
            return new LoginJwtAuth();
        } else {
            self::$authAdapter = 'session';
            return new LoginSessionAuth();
        }
    }

    public static function isJwtAuth(): bool
    {
        return self::$authAdapter == 'jwt';
    }

    /**
     * 设置登录信息
     * @param SystemUser $user
     * @return string
     * @throws \Exception
     */
    public static function makeLogin(SystemUser $user): string
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