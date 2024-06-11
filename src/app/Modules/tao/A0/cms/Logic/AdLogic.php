<?php

namespace App\Modules\tao\A0\cms\Logic;

use App\Modules\tao\A0\cms\Models\CmsAd;

class AdLogic
{
    public static function IndexList()
    {

        return CmsAd::queryBuilder()
            ->int('at_index', 1)
            ->int('status', 1)
            ->and(CmsAd::activeCondition(time()), true)
            ->order('sort desc, id desc')
            ->findColumn(['id', 'cover', 'title', 'link', 'kind', 'tag']);
    }
}