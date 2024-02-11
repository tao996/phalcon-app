<?php

namespace app\Modules\tao\Services;

use app\Modules\tao\Models\SystemUploadfile;

class UploadfileService
{
    public static function getImages(string|array $imageIds, int $userId = 0): array
    {
        if (empty($imageIds)) {
            return [];
        }
        if (is_string($imageIds)) {
            $imageIds = explode(',', $imageIds);
        }
        return SystemUploadfile::queryBuilder()->int('user_id', $userId)
            ->inInt('id', $imageIds)
            ->columns('id, url, summary')->find();
    }
}