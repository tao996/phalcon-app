<?php

namespace app\Modules\tao\A0\city\Config;

class Data
{
    const PlatformDouyin = 1;
    const PlatformWeishi = 2;
    const PlatformKuaishou = 3;

    const MapPlatform = [
        self::PlatformDouyin => ['douyin', '抖音'],
        self::PlatformWeishi => ['weishi', '微视'],
        self::PlatformKuaishou => ['kuaishou', '快手'],
    ];

    const UnfoundMap = ['unknown', '未知类型'];

    public static function getMapPlatform(int $platform): array
    {
        return self::MapPlatform[$platform] ?? self::UnfoundMap;
    }
}