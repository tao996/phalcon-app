<?php

namespace App\Modules\tao\Services\Auth;

use App\Modules\tao\Models\SystemUser;

interface LoginAuth
{

    public static function hasLoginKey(): bool;

    /**
     * 获取登录用户信息
     * @return SystemUser|null
     */
    public function getUser(): SystemUser|null;

    /**
     * 保存用户信息
     * @param array $user
     * @return mixed 登录标识 token/jwtToken 其它
     */
    public function saveUser(array $user): mixed;

    /**
     * 退出登录
     * @return void
     */
    public function destroy(): void;
}