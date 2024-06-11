<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Models\SystemUploadfile;
use Phax\Utils\MyData;

class UploadfileService
{
    public static function getImages(string|array $imageIds, int $userId = 0): array
    {
        if (empty($imageIds)) {
            return [];
        }
        if (is_string($imageIds)) {
            $imageIds = explode(',', $imageIds);
            $imageIds = MyData::getInts($imageIds);
        }
        return SystemUploadfile::queryBuilder()->int('user_id', $userId)
            ->inInt('id', $imageIds)
            ->columns('id, url, summary')->find();
    }
}