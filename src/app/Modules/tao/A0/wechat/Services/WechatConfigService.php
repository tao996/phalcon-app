<?php

namespace app\Modules\tao\A0\wechat\Services;

use app\Modules\tao\A0\wechat\Models\WechatConfig;
use app\Modules\tao\Services\ConfigService;

class WechatConfigService
{
    private const cacheKey = 'tao_wechat_config';

    public static function rows(): array
    {
        if (cache()->has(self::cacheKey)) {
            return (array)cache()->get(self::cacheKey);
        }
        return self::forceCache();
    }

    public static function forceCache(): array
    {
        static $cache = null;
        if (!is_null($cache)) {
            return $cache;
        }

        $data = WechatConfig::queryBuilder()->findColumn('name,value');
        $rows = array_column($data, 'value', 'name');
        if (!cache()->set(self::cacheKey, $rows)) {
            $cache = $rows;
            logger()->error('cache tao.wechat.config failed:' . __CLASS__);
        }
        return $rows;
    }

    public static function getWith(string $name, int|string $default = '')
    {
        $data = self::rows();
        if (isset($data[$name]) && ConfigService::notEmptyValue($data[$name])) {
            return $data[$name];
        }
        return $default;
    }

}