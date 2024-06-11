<?php

namespace App\Modules\tao\A0\open\Models;

use App\Modules\tao\A0\open\Services\OpenAppService;
use App\Modules\tao\BaseModel;
use App\Modules\tao\A0\open\Data\Config as OpenConfig;
use Phax\Traits\SoftDelete;
use Phax\Utils\MyData;

class OpenApp extends BaseModel
{
    use SoftDelete;

    public int $sort = 0;
    public string $title = ''; // 应用名称
    public int $platform = 0; // 平台
    public string $kind = ''; // 应用类型 gzh/web/mini/work

    public string $appid = ''; // appId 或者企业应用 agentId
    public string $secret = '';// 密钥

    public string $crop_id = '';  // 企业微信 cropId
    public string $token = ''; // 令牌
    public string $enc_method = ''; // 加密方式
    public string $aes_key = ''; // 消息加密密钥
    public int $online = 1; // 线上版本


    /**
     * 签名算法（交易系统）
     * @link https://developer.open-douyin.com/docs/resource/zh-CN/mini-app/develop/server/signature-algorithm
     */
    public string $public_key = ''; // 平台公钥
    public int $pi0 = 0;
    public string $rsa_public_key = ''; // 应用公钥
    public int $pi1 = 0;
    public string $rsa_private_key = ''; // 应用私钥
    public int $pi2 = 0;
    public int $done = 0; // 证书资料是否完整（交易系统）
    public int $sandbox = 0; // 是否沙盒

    public int $status = 1; // 状态
    public string $remark = ''; // 备注

    public static function getPIndex(string $name): string
    {
        static $dd = [
            'public_key' => 'pi0',
            'rsa_public_key' => 'pi1',
            'rsa_private_key' => 'pi2'
        ];
        if (!isset($dd[$name])) {
            throw new \Exception('不存在的证书字段:' . $name);
        }
        return $dd[$name];
    }

    public function beforeValidation()
    {
        if (empty($this->appid)) {
            throw new \Exception('appid 不能为空');
        }
        if (empty($this->secret)) {
            throw new \Exception('secret 不能为空');
        }
        if (!in_array($this->kind, array_keys(OpenConfig::MapKinds))) {
            throw new \Exception('不支持的抖音应用类型');
        }
        if (self::queryBuilder()->where('appid', $this->appid)
            ->notEqual('id', $this->id)->exits()
        ) {
            throw new \Exception('重复的 appid');
        }
        switch ($this->platform) {
            case OpenConfig::Tiktok;
                break;
            case OpenConfig::Wechat;
                if ('work' == $this->kind && empty($this->crop_id)) {
                    throw new \Exception('企业微信必须填写 cropId');
                }
                break;
            default:
                throw new \Exception('不支持的平台');
        }

    }

    public function beforeSave()
    {
        if (empty($this->public_key)) {
            $this->pi0 = 0;
        }
        if (empty($this->rsa_public_key)) {
            $this->pi1 = 0;
        }
        if (empty($this->rsa_private_key)) {
            $this->pi2 = 0;
        }
        $this->done = empty($this->public_key)
        || empty($this->rsa_public_key)
        || empty($this->rsa_private_key) ? 0 : 1;
    }

    public function afterSave()
    {
        OpenAppService::cache();
    }

    public static function isMini($kind): bool
    {
        return $kind == 'mini';
    }

    /**
     * 是否公众号
     * @return bool
     */
    public static function isGzh($kind): bool
    {
        return in_array($kind, ['dyh', 'fwh']);
    }

    /**
     * 是否网页应用
     * @return bool
     */
    public static function isWeb($kind): bool
    {
        return $kind == 'web';
    }

    /**
     * 是否企业微信
     * @return bool
     */
    public static function isWork($kind): bool
    {
        return $kind == 'work';
    }

    /**
     * 创建用户账号时的绑定类型
     * @param array $app
     * @return string
     * @throws \Exception
     */
    public static function newUserBind(array $app): string
    {
        MyData::mustHasSet($app, ['platform', 'kind']);
        $bind = '';
        switch ($app['platform']) {
            case \App\Modules\tao\A0\open\Data\Config::Wechat:
                $bind = 'wechat';
                break;
            case \App\Modules\tao\A0\open\Data\Config::Tiktok:
                $bind = 'tiktok';
                break;
        }
        return $bind . ucfirst($app['kind']);
    }
}