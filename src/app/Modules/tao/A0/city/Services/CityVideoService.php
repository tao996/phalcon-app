<?php

namespace app\Modules\tao\A0\city\Services;

use app\Modules\tao\A0\city\Config\Data;
use app\Modules\tao\A0\city\Models\CityVideo;

class CityVideoService
{
    public static function find(string|array $videoIds, int $userId = 0, int $status = 1): array
    {
        if (empty($videoIds)) {
            return [];
        }
        if (is_string($videoIds)) {
            $videoIds = explode(',', $videoIds);
        }
        $rows = CityVideo::queryBuilder()->int('user_id', $userId)
            ->int('status', $status)
            ->inInt('id', $videoIds)
            ->columns('id,platform,link,share_code,title,cover')->find();
        foreach ($rows as $index => $row) {
            $rows[$index]['ptitle'] = Data::getMapPlatform($row['platform'])[1];
        }
        return $rows;
    }
}