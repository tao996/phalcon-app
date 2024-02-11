<?php

namespace app\Modules\tao\A0\wechat\Models;

use app\Modules\tao\A0\wechat\Services\WechatAppService;
use app\Modules\tao\BaseModel;
use app\Modules\tao\Config\Config;
use Phax\Traits\SoftDelete;

class WechatApp extends BaseModel
{
    use SoftDelete;

    public int $status = Config::STATUS_ACTIVE;
    public string $kind = ''; // 类型 gzh/web/mini/work
    public int $online = Config::STATUS_ACTIVE; // 线上版本
    public string $title = '';

    public string $app_id = ''; // appId 或者企业应用 agentId
    public string $secret = ''; // 密钥
    public string $crop_id = '';  // 企业微信 cropId
    public string $token = ''; // 令牌
    public string $enc_method = ''; // 加密方式
    public string $aes_key = ''; // 消息加密密钥
    public int $sort = 0;

    public string $remark = '';

    /**
     * 是否小程序
     * @return bool
     */
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

    public const MapKinds = [
        'dyh' => '订阅号',
        'fwh' => '服务号',
        'web' => '网页应用',
        'mini' => '小程序',
        'work' => '企业微信',
    ];

    public function beforeValidation()
    {
        if (empty($this->app_id)) {
            throw new \Exception('app_id 不能为空');
        }
        if (empty($this->secret)) {
            throw new \Exception('secret 不能为空');
        }
        if (!in_array($this->kind, array_keys(self::MapKinds))) {
            throw new \Exception('不支持的微信应用类型');
        }
        if ('work' == $this->kind && empty($this->crop_id)) {
            throw new \Exception('企业微信必须填写 cropId');
        }
        if (self::queryBuilder()
            ->where('app_id', $this->app_id)
            ->notEqual('id', $this->id)->exits()) {
            throw new \Exception('重复的 appId');
        }
    }

    public function afterSave()
    {
        WechatAppService::forceCache();
    }
}
