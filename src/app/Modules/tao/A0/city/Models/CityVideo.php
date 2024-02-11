<?php

namespace app\Modules\tao\A0\city\Models;

use app\Modules\tao\A0\city\Config\Data;
use app\Modules\tao\BaseModel;

/**
 * 回放
 */
class CityVideo extends BaseModel
{
    public int $user_id = 0;
    public int $platform = 0; // 平台
    public string $share_code = ''; // 分享码
    public string $link = ''; // 链接
    public string $title = ''; // 标题
    public string $cover = ''; // 封面
    public int $date = 0; // 时间戳

    public int $status = 0; // 状态

    public function beforeSave()
    {
        if (!key_exists($this->platform, Data::MapPlatform)) {
            throw new \Exception('不支持的平台');
        }
        if (empty($this->share_code)) {
            throw new \Exception('必须提供分享码');
        }
        if (empty($this->link)) {
            throw new \Exception('必须填写链接地址');
        }
        if (empty($this->title)) {
            throw new \Exception('必须填写标题');
        }
    }

    public function beforeCreate()
    {
        // 检查是否重复
        if ($this->qBuilder()->int('user_id', $this->user_id)
            ->string('link', $this->link)->exits()) {
            throw new \Exception('重复的链接地址记录');
        }
    }
}