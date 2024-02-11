<?php

namespace app\Modules\tao\A0\wechat\Services;

use app\Modules\tao\A0\wechat\Models\WechatApp;
use app\Modules\tao\sdk\RedisCache;
use app\Modules\tao\sdk\SdkHelper;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use Phax\Support\Logger;


class WechatApplicationService
{
    /**
     * @param $appid string 微信 appID
     * @return \EasyWeChat\OfficialAccount\Application
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public static function getOfficialApplication(string $appid): \EasyWeChat\OfficialAccount\Application
    {
        if (empty($appid)) {
            throw new \Exception('wechat official appid is empty');
        }
        SdkHelper::easyWechat();
        $wa = WechatAppService::getWith($appid);
        if (!WechatApp::isGzh($wa['kind'])) {
            throw new \Exception('当前 appid 不是一个公众号');
        }
        try {
            $app = new \EasyWeChat\OfficialAccount\Application(
                [
                    'app_id' => $wa['app_id'], 'token' => $wa['token'],
                    'secret' => $wa['secret'], 'aes_key' => $wa['aes_key'],
                    'http' => [
                        'throw' => false,
                    ]
                ]
            );
            $cache = new RedisCache();
            $app->setCache($cache);
            return $app;
        } catch (\Exception $e) {
            if (IS_DEBUG) {
                dd($e->getMessage(), $e->getTrace());
            }
            Logger::Wrap('微信公众号配置失败:' . $appid, $e);
        }
    }

    public static function getPayApplication(string $appid): \EasyWeChat\Pay\Application
    {
        $app = WechatPayService::getPayApp($appid);
        $certDir = WechatPayService::pathCertDir();
        SdkHelper::easyWechat();
        return new \EasyWeChat\Pay\Application([
            'appid' => $app['app_id'],
            'mch_id' => $app['mch_id'],
            'private_key' => $certDir . $app['private_key'], //client_key.pem
            'certificate' => $certDir . $app['certificate'], //client_cert.pem
            'secret_key' => $app['secret_key'],
            'platform_certs' => [
                $certDir . $app['platform_cert']
            ]
        ]);
    }
}