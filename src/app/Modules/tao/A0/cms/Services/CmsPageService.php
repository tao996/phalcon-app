<?php

namespace App\Modules\tao\A0\cms\Services;

use App\Modules\tao\A0\cms\Models\CmsPage;

class CmsPageService
{
    /**
     * @param string $tag 分组或标签名
     * @param string $name 名称
     * @param int $status 状态，默认为 1
     * @return array|null
     * @throws \Exception
     */
    public static function findFirst(string $tag, string $name, int $status = 1): array|null
    {
        if ($page = CmsPage::findFirst([
            'tag' => $tag, 'name' => $name, 'status' => $status
        ])) {
            $row = $page->toArray();
            $row['content'] = CmsContentService::getContentById($page->content_id);
            return $row;
        }
        return null;
    }
}