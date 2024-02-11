<?php

namespace app\Modules\tao\sdk;


class SdkHelper
{
    public static function easyWechat(): void
    {
        static $import = false;
        if ($import === false) {
            require_once dirname(__DIR__) . '/easywechat.phar';
            $import = true;
        }
    }

}