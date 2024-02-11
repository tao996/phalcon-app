<?php

namespace app\Modules\tao\A0\city\Helper;

use app\Modules\tao\A0\cms\Helper\CmsHtmlHelper;
use app\Modules\tao\sdk\phaxui\HtmlAssets;

class CityHtmlHelper
{
    /**
     * 篮球比赛选择
     * @return void
     */
    public static function gameHtml()
    {
        include_once __DIR__ . '/html/game.phtml';

    }

    public static function gameJs()
    {
        HtmlAssets::includeAssetsFile(__DIR__ . '/html/game.js', 'js');
    }

    /**
     * 直播
     * @return void
     */
    public static function liveHtml($formItem = true): void
    {
        include_once __DIR__ . '/html/lives.phtml';

    }

    public static function liveJs(array|string $items): void
    {
        $items = CmsHtmlHelper::jsItems($items);
        $url = url('tao.city/admin.live');
        echo '<script type="text/javascript">';
        echo <<<JS
const vmLive = vueArray({id:'lives',title:'直播',prefix:'{$url}'},{$items})
JS;
        echo '</script>';
    }

    /**
     * 回放
     * @return void
     */
    public static function videoHtml($formItem = true): void
    {
        include_once __DIR__ . '/html/video.phtml';
    }

    public static function videoJs(array|string $items): void
    {

        $items = CmsHtmlHelper::jsItems($items);
        $url = url('tao.city/admin.video');
        echo '<script type="text/javascript">';
        echo <<<JS
const vmVideo = vueArray({id:'videos',title:'视频',prefix:'{$url}'}, {$items})
JS;
        echo '</script>';
    }

    public static function addressHtml()
    {
        include __DIR__ . '/html/address.phtml';
    }

    public static function addressJs()
    {
        HtmlAssets::includeAssetsFile(__DIR__ . '/html/address.js', 'js');
    }

    public static function nearHtml($formItem = true)
    {
        include __DIR__ . '/html/near.phtml';
    }

    public static function nearJs($items)
    {
        $items = CmsHtmlHelper::jsItems($items);
        $url = url('tao.city/admin.near');
        echo '<script type="text/javascript">';
        echo <<<JS
const vmNear = vueArray({id:'nears',title:'周边',prefix:'{$url}'}, {$items})
JS;
        echo '</script>';
    }
}