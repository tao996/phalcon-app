<?php

namespace App\Modules\tao\A0\wechat\Services;

use App\Modules\tao\A0\open\Services\OpenConfigService;
use App\Modules\tao\A0\wechat\Models\WechatPayApp;
use Phax\Support\Logger;

class WechatPayService
{
    protected const cacheKey = 'tao.wechat.pay.app';

    public static function rows(): array
    {
        static $cache = null;
        if (!is_null($cache)) {
            return $cache;
        }
        if (cache()->has(self::cacheKey)) {
            $cache = json_decode(cache()->get(self::cacheKey, ''), true);
            return $cache;
        }
        return self::forceCache();
    }

    /**
     * 强制缓存 wechat_pay_app 列表
     * @return array
     */
    public static function forceCache(): array
    {
        if ($cache = WechatPayApp::queryBuilder()
            ->where('done', 1)
            ->findColumn(['id', 'appid', 'mchid', 'private_key', 'certificate', 'secret_key',
                'v2_secret_key', 'platform_cert'], 'appid')) {
            if (!cache()->set(self::cacheKey, json_encode($cache))) {
                logger()->error('cache wechat.pay.app failed:' . __CLASS__);
            }
            return $cache;
        }
        return [];
    }

    /**
     * 获取配置信息
     * @param string $appid
     * @return array
     * @throws \Exception
     */
    public static function getPayApp(string $appid): array
    {
        if ($rows = self::rows()) {
            if (isset($rows[$appid])) {
                return $rows[$appid];
            }
        }
        throw new \Exception('could not find wechat pay app :' . $appid);
    }

    /**
     * 保存证书的路径，以 '/' 结尾
     * @return string
     * @throws \Exception
     */
    public static function pathCertDir(): string
    {
        $dir = PATH_STORAGE_DATA . 'pay/';
        if (!file_exists($dir)) {
            if (mkdir($dir)) {
                return $dir;
            } else {
                throw new \Exception('could not mkdir the pay cert direction');
            }
        }
        return $dir;
    }

    /**
     * 获取默认的支付服务号 appid，可能为空
     * @return string
     */
    public static function getPayAppid(): string
    {
        return OpenConfigService::getWith('pay_appid', '');
    }

    /**
     * 处理微信支付
     * @param array $postData 提示的数据
     * @param \EasyWeChat\Pay\Application $app
     * @return array 返回可直接用于 js 支付的数据
     */
    public static function wrapJsapi(array $postData, \EasyWeChat\Pay\Application $app): array
    {
        $response = $app->getClient()
            ->postJson("v3/pay/transactions/jsapi", $postData);
        try {
            // 验证返回值签名
            $app->getValidator()->validate($response->toPsrResponse());
        } catch (\Exception $e) {
            Logger::Wrap('wechat jsapi response sign failed', $e);
        }
        $data = $response->toArray(false);
//        Logger::info($data, $postData);
        if (!isset($data['prepay_id'])) {
            if (isset($data['message'])) {
                throw new \Exception($data['message']);
            }
            throw new \Exception('生成 prepayId 错误');
        }
        return self::wrapJsapiPrepayId($data, $app);
    }

    /**
     * 处理微信支付
     * @param array $responseData 接口返回的数据
     * @param \EasyWeChat\Pay\Application $app
     * @return array 返回可直接用于 js 支付的数据
     */
    public static function wrapJsapiPrepayId(array $responseData, \EasyWeChat\Pay\Application $app): array
    {
        $utils = $app->getUtils();
        return $utils->buildBridgeConfig($responseData['prepay_id'], $app->getConfig()->get('app_id'));
    }
}