<?php

namespace app\Modules\tao\A0\wechat\Services;

use app\Modules\tao\Config\Config;
use app\Modules\tao\A0\wechat\Models\WechatApp;

class WechatAppService
{
    protected const cacheKey = 'tao.wechat.app';

    public static function rows(): array
    {
        static $cache = null;
        if (!is_null($cache)) {
            return $cache;
        }
        if (cache()->has(self::cacheKey)) {
            $cache = json_decode(cache()->get(self::cacheKey, ''), true);
            return $cache;
        }
        return self::forceCache();
    }

    public static function forceCache(): array
    {
        $cache = WechatApp::queryBuilder()
            ->where('status', Config::STATUS_ACTIVE)
            ->findColumn(['id', 'kind', 'title', 'app_id', 'secret', 'crop_id', 'token', 'enc_method', 'aes_key'], 'app_id');

        if (!cache()->set(self::cacheKey, json_encode($cache))) {
            logger()->error('缓存微信应用配置信息失败:' . __CLASS__);
        }
        return $cache;
    }

    public static function getWith($app_id)
    {
        $data = self::rows();
        if (isset($data[$app_id])) {
            return (array)$data[$app_id];
        }
        throw new \Exception('没有找到(' . $app_id . ')的微信应用配置');
    }

    public static function kindCompare(string $appid, string $kind): bool
    {
        $wc = self::getWith($appid);
        switch ($kind) {
            case 'mini':
                return WechatApp::isMini($wc['kind']);
            case 'gzh':
                return WechatApp::isGzh($wc['kind']);
            case 'dyh':
                return $wc['kind'] == 'dyh';
            case 'fwh':
                return $wc['kind'] == 'fwh';
            case 'web':
                return WechatApp::isWeb($wc['kind']);
            case 'work':
                return WechatApp::isWork($wc['kind']);
            default:
                throw new \Exception('wechat kind value is not allow:' . $kind);
        }
    }

}