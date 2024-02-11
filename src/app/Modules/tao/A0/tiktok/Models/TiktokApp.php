<?php

namespace app\Modules\tao\A0\tiktok\Models;

use app\Modules\tao\BaseModel;
use Phax\Traits\SoftDelete;

class TiktokApp extends BaseModel
{
    use SoftDelete;

    public string $title = ''; // 应用名称
    public string $kind = ''; // 应用类型
    public string $appid = ''; // AppID
    public string $secret = '';

    public string $public_key = ''; // 平台公钥
    public int $pi0 = 0;
    public string $rsa_public_key = ''; // 应用公钥
    public int $pi1 = 0;
    public string $rsa_private_key = ''; // 应用私钥
    public int $pi2 = 0;

    public int $done = 0; // 证书资料是否完整
    public int $sandbox = 0; // 是否沙盒
    public string $remark = ''; // 备注

    public const MapKinds = [
        'mini' => '小程序'
    ];

    public static function getPIndex(string $name): string
    {
        static $dd = ['public_key' => 'pi0', 'rsa_public_key' => 'pi1', 'rsa_private_key' => 'pi2'];
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

        if (!in_array($this->kind, array_keys(self::MapKinds))) {
            throw new \Exception('不支持的抖音应用类型');
        }
        if (self::queryBuilder()->where('appid', $this->appid)
            ->notEqual('id', $this->id)->exits()
        ) {
            throw new \Exception('重复的 appid');
        }
    }

    public function beforeSave()
    {
        $this->done = empty($this->public_key)
        || empty($this->rsa_public_key)
        || empty($this->rsa_private_key) ? 0 : 1;
    }
}