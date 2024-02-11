<?php

namespace app\Modules\tao\A0\city\Models;

use app\Modules\tao\BaseModel;
use Phax\Traits\SoftDelete;

/**
 * 球员
 */
class CityPlayer extends BaseModel
{
    use SoftDelete;

    public int $term_id = 0;
    public string $avatar = ''; // 头像
    public string $name = ''; // 名称
    public string $code = ''; // 球衣号码
    public int $sex = 1; // 性别
    public int $sort = 0; // 排序
    public int $status = 1; // 状态
    public string $tiktok = ''; // 抖音号

    public function beforeSave()
    {
        if (empty($this->term_id)){
            throw new \Exception('必须指定所属球队');
        }
        if (empty($this->name)) {
            throw new \Exception('必须输入球员名称');
        }

    }
}