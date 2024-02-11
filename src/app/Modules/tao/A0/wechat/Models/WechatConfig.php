<?php

namespace app\Modules\tao\A0\wechat\Models;

use app\Modules\tao\BaseModel;

class WechatConfig extends BaseModel
{
    protected string|bool $autoWriteTimestamp = false;

    public string $name = '';
    public string $value = '';
    public string $remark = '';

    public function updateValue($name, $value): bool
    {
        $sql = 'UPDATE ' . $this->getSource() . ' SET value=? WHERE name=?';
        return db()->execute($sql,
            [$value, $name],
            [\PDO::PARAM_STR, \PDO::PARAM_STR]
        );
    }

}