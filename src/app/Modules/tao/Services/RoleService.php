<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Models\SystemRole;

class RoleService
{

    public static function getIds(array $roles): array
    {
        if (!empty($roles)) {
            $rows = SystemRole::queryBuilder()->inString('name', $roles)
                ->where(['status' => 1])->columns('id')->find();
            return array_column($rows,'id');
        }
        return [];
    }
}