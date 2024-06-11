<?php

namespace App\Modules\tao\A0\open\Services;

use App\Modules\tao\A0\open\Models\OpenApp;
use App\Modules\tao\A0\open\Sdk\SdkHelper;
use App\Modules\tao\sdk\RedisCache;
use EasyTiktok\MiniApp\Application;
use Phax\Support\Logger;
use Phax\Utils\MyData;

class TiktokApplicationService
{
    public static function getMiniApplication(array $app)
    {
        MyData::mustHasSet($app, ['appid', 'secret', 'sandbox', 'kind']);
        SdkHelper::autoload();

        if (!OpenApp::isMini($app['kind'])) {
            throw new \Exception('tiktok mini appid is invalid');
        }

        try {
            $app = new Application([
                'app_id' => $app['appid'],
                'secret' => $app['secret'],
                'sandbox' => $app['sandbox'],
                'http' => ['throw' => true]
            ]);
            $cache = new RedisCache();
            $app->setCache($cache);
            return $app;
        } catch (\Exception $e) {
            if (IS_DEBUG) {
                dd($e->getMessage(), $e->getTrace());
            }
            Logger::Wrap('Tiktok 小程序配置失败:' . $app['appid'], $e);
        }
    }
}