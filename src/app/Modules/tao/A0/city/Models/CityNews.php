<?php

namespace app\Modules\tao\A0\city\Models;

use app\Modules\tao\A0\city\Config\Data;
use app\Modules\tao\BaseModel;
use Phax\Traits\SoftDelete;


class CityNews extends BaseModel
{
    use SoftDelete;

    const KindView = 2; // 景点
    const KindGame = 3; // 比赛
    const KindHoliday = 4; // 节日

    const MapKinds = [
        self::KindView => ['view', '景点'],
        self::KindGame => ['game', '比赛'],
        self::KindHoliday => ['holiday', '节日'],
    ];

    public static function getMapKind(int $kind)
    {
        return self::MapKinds[$kind] ?? Data::UnfoundMap;
    }

    const KeysGame = [
        'id1', 'name1', 'color1',
        'id2', 'name2', 'color2',
    ];

    public int $kind = 0; // 类型
    public int $status = 0;
    public string $title = ''; // 标题
    public string $summary = ''; // 简介
    public string $list = ''; // 列表图
    public string $banner = ''; // 横幅图
    public string $address = ''; // 地址
    public float $lng = 0;
    public float $lat = 0; // 坐标
    public int $dt1 = 0; // 开始时间（时间戳）
    public int $dt2 = 0; // 结束时间（时间戳）
    public string $warning = ''; // 出行提示
    public string $tag = ''; // TAG

    public string $metadata = ''; // kind 原始信息
    public string $image_ids = ''; // 图集 ID
    public string $liver_ids = ''; // 直播间(ID)
    public string $video_ids = ''; // 回放视频（ID）
    public string $near_ids = ''; // 周边 ID

    public int $ad_id = 0; // cms.ad_id
    public int $content_id = 0; // cms_content.id
    public int $hot = 0; // 热度

    public function beforeSave()
    {
        if ($this->kind > 0) {
            if (!in_array($this->kind, array_keys(self::MapKinds))) {
                throw new \Exception('不允许的类型');
            }
        } else {
            $this->metadata = '';
        }
        if (empty($this->title)) {
            throw new \Exception('必须填写标题');
        }
        if (empty($this->address)) {
            throw new \Exception('必须填写地址');
        }
        if ($this->dt1 < 1) {
            throw new \Exception('必须填写开始时间');
        }
    }


}