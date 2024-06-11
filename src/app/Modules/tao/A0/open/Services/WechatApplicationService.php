<?php

namespace App\Modules\tao\A0\open\Services;

use App\Modules\tao\A0\open\Models\OpenApp;
use App\Modules\tao\A0\wechat\Services\WechatPayService;
use App\Modules\tao\sdk\RedisCache;
use App\Modules\tao\sdk\SdkHelper;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\MiniApp\Application;
use Phax\Support\Logger;
use Phax\Utils\MyData;


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
        $wa = OpenAppService::getWith($appid);
        if (!OpenApp::isGzh($wa['kind'])) {
            throw new \Exception('不是公众号 appid');
        }
        try {
            $app = new \EasyWeChat\OfficialAccount\Application(
                [
                    'app_id' => $wa['appid'], 'token' => $wa['token'],
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
            'app_id' => $app['appid'],
            'mch_id' => $app['mchid'],
            'private_key' => $certDir . $app['private_key'], //client_key.pem
            'certificate' => $certDir . $app['certificate'], //client_cert.pem
            'secret_key' => $app['secret_key'],
            'platform_certs' => [
                $certDir . $app['platform_cert']
            ]
        ]);
    }

    public static function getMiniApplication(array $app)
    {
        MyData::mustHasSet($app, ['appid', 'secret', 'token', 'kind', 'aes_key']);

        SdkHelper::easyWechat();

        if (!OpenApp::isMini($app['kind'])) {
            throw new \Exception('不是小程序 appid');
        }
        try {
            $app = new Application([
                'app_id' => $app['appid'],
                'secret' => $app['secret'],
                'token' => $app['token'], 'aes_key' => $app['aes_key'],
                'http' => [
                    'throw' => false
                ]
            ]);
            $cache = new RedisCache();
            $app->setCache($cache);
            return $app;
        } catch (\Exception $e) {
            if (IS_DEBUG) {
                dd($e->getMessage(), $e->getTrace());
            }
            Logger::Wrap('微信小程序配置失败:' . $app['appid'], $e);
        }
    }
}