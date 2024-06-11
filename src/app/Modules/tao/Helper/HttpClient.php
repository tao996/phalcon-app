<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\sdk\SdkHelper;
use EasyWeChat\Kernel\HttpClient\RequestUtil;

class HttpClient
{

    /**
     * 创建一个请求的客户端
     * @link https://symfony.com/doc/current/http_client.html
     * @param array $options
     * @return \Symfony\Contracts\HttpClient\HttpClientInterface
     */
    public static function new(array $options = []): \Symfony\Contracts\HttpClient\HttpClientInterface
    {
        SdkHelper::easyWechat();
        $options = RequestUtil::formatDefaultOptions($options);
        return \Symfony\Component\HttpClient\HttpClient::create($options);
    }
}