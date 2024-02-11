<?php

namespace app\Modules\tao\A0\app\Models;

use app\Modules\tao\BaseModel;
use Phax\Traits\SoftDelete;

class AppInfo extends BaseModel
{
    use SoftDelete;

    public string $tag = ''; // 群组，方便搜索
    public string $title = ''; // 标题
    public int $status = 1; // 状态
    public string $remark = ''; // 说明


    public function beforeSave()
    {
        if (empty($this->title)) {
            throw new \Exception('必须填写标题');
        }
    }
}