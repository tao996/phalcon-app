<?php

namespace App\Modules\tao\Services\Auth;


use Phax\Support\Config;

class AuthRedisData
{
    private static function getRedisKey(string $token): string
    {
        return Config::currentProject('phax') . ':login:' . $token;
    }

    public static function delToken(string $token): void
    {
        redis()->del(self::getRedisKey($token));
    }

    public static function setToken(string $token, mixed $value, $options = null)
    {
        redis()->set(self::getRedisKey($token), $value, $options);
    }

    public static function setTokenExpire(string $token, int $seconds)
    {
        redis()->expire(self::getRedisKey($token), $seconds);
    }

    public static function getTokenValue(string $token)
    {
        return redis()->get(self::getRedisKey($token));
    }

    /**
     * 为指定用户生成一个 token
     * @param int $userId
     * @return string
     */
    public static function generateToken(int $userId, string $kind): string
    {
        return join('.', [$userId, $kind, time()]);
    }

    public static function getUserId(string $token, string $kind): int
    {
        $tokenData = explode($kind == 'app' ? '.' : ':', $token);
//        dd($kind,$token,$tokenData);
        if (count($tokenData) != 3) {
            throw new \Exception('用户登录凭证错误:1');
        }

        if (intval($tokenData[0]) < 1 || $tokenData[1] != $kind) {
            throw new \Exception('用户登录凭证错误:2');
        }

        return $tokenData[0];
    }
}