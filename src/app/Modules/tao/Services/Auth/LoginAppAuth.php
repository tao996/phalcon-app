<?php

namespace App\Modules\tao\Services\Auth;

use App\Modules\tao\Models\SystemUser;
use Phax\Support\Logger;
use Phax\Utils\MyData;

/**
 * 通常用于小程序 mini 对请求进行加密
 */
class LoginAppAuth implements LoginAuth
{
    private static function getPostBody(): array
    {
        if (request()->getQuery('data') === 'jsonbody') {
            return request()->getJsonRawBody(true);
        }
        return request()->getPost();
    }

    public static function hasLoginKey(): bool
    {
        // token 用户ID.时间戳
        $postData = self::getPostBody();
        return isset($postData['_auth']);
    }

    public function getUser(): SystemUser|null
    {
        if ($this->hasLoginKey()) {

            $authData = self::getPostBody()['_auth'];
            MyData::mustHasSet($authData, ['token', 't', 'sign']);

            $userId = AuthRedisData::getUserId($authData['token'], 'app');
            if (!$sec = AuthRedisData::getTokenValue($authData['token'])) {
                Logger::info('AppAuth 当前登录凭证不存在或已过期');
                return null;
            }; // 用于签名的 secret
            // 包含了毫秒数的时间戳（时间戳本身也具有验签作用）
            $timestamp = intval($authData['t']);
            // 对时间戳进行验证
            $keys = $authData['keys'] ?? ''; // 需要验证的字段值
            if ($keys) {
                $signKeys = explode(',', $keys);
                sort($signKeys);
            }

            // todo 验证签名

            return SystemUser::findFirst($userId);
        }
        return null;
    }

    public function saveUser(array $user): mixed
    {
        $userId = MyData::getInt($user, 'id');
        $token = AuthRedisData::generateToken($userId, 'app'); // 已经使用了 . 号作为分割号
        // 随机码，用于生成 sign 签名
        $sec = md5(join(',', [rand(1, 100), time() + rand(100, 9999)]));
        AuthRedisData::setToken($token, $sec);
        return join('-', [$token, $sec]);
    }

    public function destroy(): void
    {
        $data = self::getPostBody();
        if (isset($data['_auth']['token'])) {
            AuthRedisData::delToken($data['_auth']['token']);
        }
    }


}