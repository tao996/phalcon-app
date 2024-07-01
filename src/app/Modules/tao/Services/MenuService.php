<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Config\Data;
use App\Modules\tao\Models\SystemMenu;
use App\Modules\tao\Models\SystemNode;
use Phax\Foundation\Router;

class MenuService
{
    public static function href($href, $type = 0, string|array $params = []): string
    {
        if ($href) {
            if (SystemNode::KIND_MODULE == $type) {
                if (str_starts_with($href, '/' . Router::ModulePrefix . '/')) {
                    return $href;
                }
                return url($href, false, true);
            } elseif (SystemNode::KIND_PROJECT == $type) {
                if (str_starts_with($href, '/' . Router::ProjectPrefix . '/')) {
                    return $href;
                }
                return url($href, false, false);
            }
        }
        return $href;
    }

    public static function homeId(): int
    {
        static $homeId = null;
        if (is_null($homeId)) {

            $homeId = SystemMenu::queryBuilder()->int('pid', Data::HOME_PID)
                ->value('id');
        }
        return $homeId;
    }
}