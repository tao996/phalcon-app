<?php

namespace App\Modules\tao\A0\open\Services;


use App\Modules\tao\A0\open\Data\Config;
use App\Modules\tao\A0\open\Helper\TiktokHelper;
use App\Modules\tao\A0\open\sdk\SdkHelper;
use Phax\Utils\MyData;

class MiniAppServer
{
    /**
     * 获取授权用户的 session_key 和 openid
     * @link https://demo.fushuilu.com/api/m/tao.open/mini-server/code2session
     * @link [抖音小程序]https://developer.open-douyin.com/docs/resource/zh-CN/mini-app/develop/server/log-in/code-2-session
     * @link [微信小程序]https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/user-login/code2Session.html
     * @param array $app
     * @param string $code 小程序 code
     * @return array ['openid'=>xxx,'session_key'=>'xxx', 'unionid'=>'xxx']
     */
    public static function code2Session(array $app, string $code)
    {
        MyData::mustHasSet($app, ['appid', 'secret', 'kind', 'platform']);

        SdkHelper::autoload();

        switch ($app['platform']) {
            case Config::Tiktok:
                $application = TiktokApplicationService::getMiniApplication($app);
                $response = $application->getClient()->postJson('api/apps/v2/jscode2session', [
                    'appid' => $app['appid'],
                    'secret' => $app['secret'],
                    'code' => $code
                ]);
                $data = TiktokHelper::openAPIResponseResult($response);
                break;
            case Config::Wechat:
                $application = WechatApplicationService::getMiniApplication($app);
                $data = $application->getUtils()->codeToSession($code);
                break;
            default:
                throw new \Exception('code2Session app platform is invalid');
        }
        return $data;
    }
}