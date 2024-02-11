<?php

namespace app\Modules\tao\A0\city\Models;

use app\Modules\tao\BaseModel;
use Phax\Traits\SoftDelete;

/**
 * 直播
 * 通常情况下是由客户端登录后，推送过来“我要直播”，此时会获取用户的 app user id 和 账号，而不需要手动添加
 */
class CityLive extends BaseModel
{
    use SoftDelete;

    public int $user_id = 0;
    public int $platform = 0; // 平台
    public string $auid = ''; // app user id
    public string $name = ''; // 账号 (方便后台管理)

    public string $qc = ''; // 直播间二维码
    public string $share_code = ''; // 直播间分享码
    public int $status = 1; // 上线

    public function beforeSave()
    {
        if (empty($this->qc) && empty($this->share_code)) {
            throw new \Exception('直播间二维码和分享码不能同时为空');
        }
    }

    public function beforeCreate()
    {

        if ($this->qc && $this->qBuilder()->int('user_id', $this->user_id)
                ->string('qc', $this->qc)->exits()) {
            throw new \Exception('重复的直播间二维码');
        }
        if ($this->share_code && $this->qBuilder()->int('user_id', $this->user_id)
                ->string('share_code', $this->share_code)) {
            throw new \Exception('重复的分享码');
        }
    }
}