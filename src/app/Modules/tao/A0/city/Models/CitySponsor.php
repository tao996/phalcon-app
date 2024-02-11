<?php

namespace app\Modules\tao\A0\city\Models;

use app\Modules\tao\BaseModel;
use Phax\Traits\SoftDelete;

/**
 * 赞助商
 */
class CitySponsor extends BaseModel
{
    use SoftDelete;

    public int $term_id = 0; // 球队
    public string $name = ''; // 名称
    public int $money = 0; // 金额
    public int $date = 0; // 日期
    public int $status = 1; // 状态
    public string $other = ''; // 备注（物品赞助）

    public function beforeSave()
    {
        if (empty($this->name)) {
            throw new \Exception('必须填写赞助商名称');
        }
    }
}