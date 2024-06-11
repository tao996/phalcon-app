<?php

namespace App\Modules\tao\Services;

class SecretService
{
    /**
     * 对地址(图片/内链)进行安全处理
     * @param string $url
     * @return string
     */
    public static function innerURL(string $url): string
    {
        // todo 对域名进行限制
        return $url;
    }
}