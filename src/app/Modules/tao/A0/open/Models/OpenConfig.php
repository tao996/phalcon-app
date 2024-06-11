<?php

namespace App\Modules\tao\A0\open\Models;

use App\Modules\tao\BaseModel;

class OpenConfig extends BaseModel
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