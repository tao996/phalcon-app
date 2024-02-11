<?php

namespace app\Modules\tao\A0\city\Services;

use app\Modules\tao\A0\city\Config\Data;
use app\Modules\tao\A0\city\Models\CityLive;

class CityLiveService
{
    public static function find(string|array $liveIds, int $userId = 0, int $status = 1): array
    {
        if (empty($liveIds)) {
            return [];
        }
        if (is_string($liveIds)) {
            $liveIds = explode(',', $liveIds);
        }
        $rows = CityLive::queryBuilder()->int('user_id', $userId)
            ->int('status', $status)
            ->inInt('id', $liveIds)
            ->columns('id,platform,name,share_code,qc,status')->find();
        foreach ($rows as $index => $row) {
            $rows[$index]['ptitle'] = Data::getMapPlatform($row['platform'])[1];
        }
        return $rows;
    }
}