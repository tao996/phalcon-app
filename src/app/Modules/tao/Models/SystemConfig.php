<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseModel;

class SystemConfig extends BaseModel
{
    public string $name = '';
    public string $gname = ''; // 分组名
    public string $value = '';
    public string $remark = '';
    public int $sort = 0;

    public function updateValue($gname, $name, $value)
    {
//        return $this->modelsManager->executeQuery('UPDATE '.__CLASS__.' SET
//        value=?0 WHERE gname=?1 AND name=?2',
//            [0 => $value, $gname, $name]);
        return db()->execute('UPDATE ' . $this->getSource() . ' SET value=? WHERE gname=? AND name=?',
            [$value, $gname, $name],
            [\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR]
        );
    }

    public function tableTitle(): string
    {
        return '配置';
    }
}