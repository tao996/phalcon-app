<?php

namespace app\Modules\tao\A0\city\Models;

use app\Modules\tao\BaseModel;
use Phax\Traits\SoftDelete;

/**
 * 球队
 */
class CityTerm extends BaseModel
{
    use SoftDelete;

    public string $avatar = ''; // 头像
    public string $name = ''; // 名称
    public string $nickname = ''; // 简称
    public string $address = '';
    public string $leader = ''; // 领队
    public int $status = 1;
    public int $sort = 0;

    public function beforeSave()
    {
        if (empty($this->name)) {
            throw new \Exception('必须填写球队名称');
        }
        if (empty($this->address)) {
            throw new \Exception('必须填写地区');
        }
        if ($this->qBuilder()->string('name', $this->name)
            ->notEqual('id', $this->id)->exits()) {
            throw new \Exception('重复的球队名称');
        }
    }
}