<?php

namespace App\Modules\tao\Config;

class Data
{
    /**
     * 首页的 PID
     */
    const HOME_PID = 99999999;

    const Gmail = 'gmail';
    const TiktokMini = 'tiktokMini';
    const WechatMini = 'wechatMini';
    const WechatOfficial = 'wechatOfficial';

    const MapBinds = [
        self::Gmail => 'Google',
        self::TiktokMini => '抖音小程序',
        self::WechatMini => '微信小程序',
        self::WechatOfficial => '微信公众号',
    ];

    const AccessUser = 'user'; // 用户可见
    const AccessSuperAdmin = 'superAdmin';

    const MapAccess = [
        self::AccessUser => '用户',
        self::AccessSuperAdmin => '超级管理员'
    ];
}