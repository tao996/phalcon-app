<?php

namespace app\Modules\tao\A0\city\Models;

use app\Modules\tao\A0\city\Config\Data;
use app\Modules\tao\BaseModel;

class CityNear extends BaseModel
{
    const KindFood = 1;
    const KindPlay = 2;
    const KindStore = 3;
    const KindLive = 4;

    const MapKinds = [
        self::KindFood => ['food', '美食'],
        self::KindPlay => ['play', '娱乐'],
        self::KindStore => ['store', '商场'],
        self::KindLive => ['live', '住宿'],
    ];

    public static function getMapKind($kind): array
    {
        return self::MapKinds[$kind] ?? Data::UnfoundMap;
    }

    public int $user_id = 0; // 用户 ID
    public int $kind = 0; // 类型
    public string $title = ''; // 名称
    public string $tag = '';
    public string $address = ''; // 地址
    public float $lng = 0; // 经度
    public float $lat = 0; // 纬度

    public string $banner = ''; // 横幅图
    public string $list = ''; // 列表图
    public string $summary = ''; // 简介

    public string $image_ids = ''; // 图集 ID
    public string $video_ids = ''; // 视频 ID

    public int $status = 1; // 状态
    public int $sort = 0;
    public int $hot = 0;
    public int $top = 0;

}