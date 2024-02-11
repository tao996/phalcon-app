<?php

namespace app\Modules\tao\Services;

use app\Modules\tao\Models\SystemUser;
use app\Modules\tao\Services\Auth\LoginJwtAuth;
use app\Modules\tao\Services\Auth\LoginSessionAuth;

class LoginService
{

    /**
     * 设置登录信息
     * @param SystemUser $user
     * @return string
     * @throws \Exception
     */
    public static function makeLogin(SystemUser $user): string
    {
        $sessionType = request()->getPost('session');
        if ('jwt' == $sessionType) {
            return (new LoginJwtAuth())->saveUser($user->toArray());
        } else {
            (new LoginSessionAuth())->saveUser($user->toArray());
            return '';
        }
    }

    /**
     * 检查是否登录
     * @return bool
     */
    public static function tryLogin(): bool
    {
        return LoginUser::instance()->isLogin();
    }

    /**
     * 获取登录的用户
     * @return SystemUser
     * @throws \Exception
     */
    public static function getLoginUser()
    {
        return LoginUser::instance()->user();
    }
}