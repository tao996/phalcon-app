<?php

namespace app\Modules\tao\A0\cms\Models;
// 广告
use app\Modules\tao\BaseModel;
use Phax\Support\Validate;
use Phax\Traits\SoftDelete;

class CmsAd extends BaseModel
{
    use SoftDelete;

    public int $user_id = 0;
    public int $begin_at = 0; // 开始时间
    public int $end_at = 0; // 结束时间
    public string $cover = ''; // 封面
    public string $title = ''; // 标题
    public string $link = ''; // 链接或路径
    public int $at_banner = 0; // 横幅
    public int $at_index = 0; // 首页
    public int $at_list = 0; // 列表页
    public int $at_page = 0; // 内页
    public string $tag = ''; // 其它
    public int $sort = 0; // 排序

    public int $status = 0; // 状态
    public string $remark = ''; // 备注
    // 标识
    public int $live = 0; // 直播
    public int $ad = 0; // 广告

    public function beforeSave()
    {
        if (empty($this->title)) {
            throw new \Exception('必须填写广告标题');
        }
        if (empty($this->link)) {
            throw new \Exception('必须填写链接或路径');
        }
    }
}