<?php

namespace app\Modules\tao\A0\city\Services;

use app\Modules\tao\A0\cms\Services\CmsContentService;
use app\Modules\tao\Services\UploadfileService;

class CityNewsService
{
    public static function appendInfo(&$row)
    {
        $row['images'] = UploadfileService::getImages($row['image_ids']);
        $row['lives'] = CityLiveService::find($row['live_ids']);
        $row['videos'] = CityVideoService::find($row['video_ids']);
        $row['nears'] = CityNearService::find($row['near_ids']);
        $row['content'] = CmsContentService::getContentById($row['content_id']);
    }
}