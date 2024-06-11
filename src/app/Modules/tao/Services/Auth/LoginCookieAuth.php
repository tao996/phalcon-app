<?php

namespace App\Modules\tao\Services\Auth;

use App\Modules\tao\Models\SystemUser;
use Phax\Mvc\Response;
use Phax\Support\Logger;
use Phax\Utils\MyData;

/**
 * 从 cookie 中读取凭证（目前用于 web 端）
 */
class LoginCookieAuth implements LoginAuth
{
    private int $expireSeconds = 3600;

    public static function hasLoginKey(): bool
    {
        return cookies()->has('Authorization');
    }

    private function getAuthorizationValue(): string
    {
        try {
            return cookies()->get('Authorization')->getValue();
        } catch (\Exception $e) {
            return '';
        }
    }

    public function getUser(): SystemUser|null
    {
        if ($this->hasLoginKey()) {
            if ($token = $this->getAuthorizationValue()) {
                $userId = AuthRedisData::getUserId($token, 'web');
                $data = AuthRedisData::getTokenValue($token);
                if ($data != 1) {
                    Logger::info('CookieAuth 当前登录凭证不存在或已过期:' . $token);
                    return null;
                }

                if ($user = SystemUser::findFirst($userId)) {
                    AuthRedisData::setTokenExpire($token, $this->expireSeconds);
                    return $user;
                }

            }
        }
        return null;
    }

    public function saveUser(array $user): mixed
    {
        $userId = MyData::getInt($user, 'id');
        $token = join(':', [$userId, 'web', time()]); // 由 3 部分组成
        // 可以设置保存用户的设备信息
        AuthRedisData::setToken($token, 1, $this->expireSeconds); // 默认 1 个小时
        Response::cookieSet('Authorization', $token);
        return $token;
    }

    public function destroy(): void
    {
        if ($token = $this->getAuthorizationValue()) {
            AuthRedisData::delToken($token);
            cookies()->get('Authorization')->delete();
            Response::cookieRemove();
        }
    }
}