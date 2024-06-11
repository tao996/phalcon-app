<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Models\SystemConfig;
use Phalcon\Cache\Exception\InvalidArgumentException;

class ConfigService
{
    private const cacheKey = 'tao_system_config';

    /**
     * @throws InvalidArgumentException
     */
    public static function rows(): array
    {
        static $cache = null;
        if (!is_null($cache)) {
            return $cache;
        }
        if (cache()->has(self::cacheKey)) {
            $cache = (array)cache()->get(self::cacheKey);
            return $cache;
        }
        return self::forceCache();
    }

    /**
     * 强制缓存配置信息，注意内部是以 gname.name = value 方式保存
     * @return array
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    public static function forceCache(): array
    {
        $data = SystemConfig::queryBuilder()->findColumn('name,gname,value');
        $rows = [];
        foreach ($data as $row) {
            $rows[$row['gname'] . '.' . $row['name']] = $row['value'];
        }
        if (!cache()->set(self::cacheKey, $rows)) {
            logger()->error('缓存系统配置信息失败：' . __CLASS__);
        }
        return $rows;

    }

    /**
     * 查询分组配置信息
     * @param string $gname
     * @param bool $resetKey 将 name 重置为 key
     * @return array
     */
    public static function groupRows(string $gname, bool $resetKey = true): array
    {
        $rows = [];
//        dd(self::rows());
        foreach (self::rows() as $key => $value) {
            if (str_starts_with($key, $gname)) {
                if ($resetKey) {
                    $rows[explode('.', $key)[1]] = $value;
                } else {
                    $rows[$key] = $value;
                }
            }
        }
        return $rows;
    }

    /**
     * 获取配置信息内容
     * @param string $path 由 gname.name 组件
     * @param mixed|string $default 默认值
     * @return mixed|string
     */
    public static function getWith(string $path, mixed $default = ''): mixed
    {
        static $data = null;
        if (is_null($data)) {
            $data = ConfigService::rows();
        }
        return $data[$path] ?? $default;
    }


    /**
     * 查询配置分组名称
     * @return array
     */
    public static function findGname(): array
    {
        return SystemConfig::queryBuilder()->distinct('gname')->find();
    }

    /**
     * 配置的值是否为空
     * @param string $value
     * @return bool
     */
    public static function emptyValue(string $value): bool
    {
        return empty($value) || trim($value) == "0";
    }

    public static function notEmptyValue(string $value): bool
    {
        return !self::emptyValue($value);
    }

    /**
     * 是否为启用值，通常为 checkbox
     * @param string $value
     * @return bool
     */
    public static function activeValue(string $value): bool
    {
        return intval($value) == 1;
    }
}