<?php

namespace App\Modules\tao\A0\open\Helper;

use EasyWeChat\Kernel\HttpClient\Response;

class TiktokHelper
{
    /**
     * 对抖音服务端 OpenAPI 接口返回值进行判断
     * @return mixed
     */
    public static function openAPIResponseResult(Response|Symfony\Contracts\HttpClient\ResponseInterface $response)
    {
        $data = $response->toArray(true);
        if ($data['err_tips'] != "success") {
            throw new \Exception($data['err_tips']);
        }
        return $data['data'];
    }
}