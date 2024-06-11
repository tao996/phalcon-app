<?php

namespace App\Modules\tao\A0\open\Data;

class Config
{
    const Wechat = 1;
    const Tiktok = 2;

    public const MapKinds = [
        'dyh' => '订阅号',
        'fwh' => '服务号',
        'web' => '网页应用',
        'mini' => '小程序',
        'work' => '企业微信',
    ];

    public const MapPlatform = [
        self::Wechat => '微信',
        self::Tiktok => '抖音',
    ];
}