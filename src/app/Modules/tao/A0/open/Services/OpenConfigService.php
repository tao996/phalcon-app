<?php

namespace App\Modules\tao\A0\open\Services;

use App\Modules\tao\A0\open\Models\OpenConfig;
use App\Modules\tao\Services\ConfigService;

class OpenConfigService
{
    private const cacheKey = 'tao_open_config';

    public static function rows(): array
    {
        if (cache()->has(self::cacheKey)) {
            return (array)cache()->get(self::cacheKey);
        }
        return self::cache();
    }

    /**
     * 缓存配置信息
     * @return array
     */
    public static function cache(): array
    {

        $data = OpenConfig::queryBuilder()->findColumn('name,value');
        $rows = array_column($data, 'value', 'name');
        if (!cache()->set(self::cacheKey, $rows)) {
            logger()->error('cache open.config failed:' . __CLASS__);
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