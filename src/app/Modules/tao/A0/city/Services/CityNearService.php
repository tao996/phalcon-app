<?php

namespace app\Modules\tao\A0\city\Services;

use app\Modules\tao\A0\city\Models\CityNear;

class CityNearService
{
    public static function find(string|array $nearIds, int $userId = 0, int $status = 1): array
    {
        if (empty($nearIds)) {
            return [];
        }

        if (is_string($nearIds)) {
            $nearIds = explode(',', $nearIds);
        }
        $rows = CityNear::queryBuilder()->int('user_id', $userId)
            ->int('status', $status)
            ->inInt('id', $nearIds)
            ->columns('id,kind,title,tag,address,lng,lat,banner,list,summary,image_ids,video_ids')->find();
        foreach ($rows as $index => $row) {
            $rows[$index]['ktitle'] = CityNear::getMapKind($row['kind']);
        }
        return $rows;
    }
}