<?php

namespace App\Modules\tao\Services;

use Phalcon\Cache\Exception\InvalidArgumentException;

class EventService
{
    public static function updateMenu($userId = 0)
    {
        // todo 更新用户菜单
    }

    /**
     * 强制更新系统缓存
     * @throws InvalidArgumentException
     */
    public static function uploadSystemConfig(): void
    {
        ConfigService::forceCache();
    }

}