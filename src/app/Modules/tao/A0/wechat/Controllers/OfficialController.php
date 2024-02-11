<?php

namespace app\Modules\tao\A0\wechat\Controllers;

use app\Modules\tao\A0\wechat\BaseWechatServeController;
use app\Modules\tao\A0\wechat\Logics\WechatUserLogic;
use app\Modules\tao\A0\wechat\Services\WechatApplicationService;
use app\Modules\tao\A0\wechat\Services\WechatUserService;
use Phax\Support\Logger;
use Phax\Utils\Data;

/**
 * 公众号
 * @link [EasyWeChat](https://easywechat.com/6.x/official-account/index.html)
 * @link [微信公众号文档地址](https://developers.weixin.qq.com/doc/offiaccount/Basic_Information/Access_Overview.html)
 * [微信公众平台测试号](https://mp.weixin.qq.com/debug/cgi-bin/sandbox?t=sandbox/login)
 */
class OfficialController extends BaseWechatServeController
{
    /**
     * 接入示例 https://你的域名/api/m/tao.wechat/official?appid=yourwechatappid
     */
    public function indexAction()
    {

        $query = $this->request->getQuery();
        // 直接跳过不处理
        if (isset($query['skip'])) {
            exit("");
        }
        Data::mustHasSet($query, ['appid']);
        // 可以添加其它参数，防止接口公开时被攻击
//        if (!isset($query['t']) && $query['t'] != 'xxx') {exit('query lost.');}
        $app = WechatApplicationService::getOfficialApplication($query['appid']);
        $server = $app->getServer();

        if (!isset($query['echostr'])) {
            $server->with(function (\EasyWeChat\OfficialAccount\Message $message,
                                    \Closure                            $next) use ($app, $server) {
                $data = $message->toArray();
                $openid = $data['FromUserName'];

                if (IS_DEBUG) {
                    Logger::info($data);
                }

                // 事件推送
                // https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Receiving_event_pushes.html
                if ($message->MsgType == "event") {
                    switch ($message->Event) {
                        case "subscribe": // 订阅
                            $uo = WechatUserLogic::officialUser($app, $openid);
                            if ($uo->sub == 0) {
                                $uo->sub = 1;
                                WechatUserService::saveSubStatus($uo);
                            }
                            return "感谢关注";
                        case "unsubscribe":
                            WechatUserLogic::unsubscribe($data);
                            return "";
                        case "CLICK": // 自定义菜单事件
                            break;
                        case "VIEW": // 点击菜单跳转链接
                            break;
                    }
                    // 普通消息
                    // https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Receiving_standard_messages.html
                } elseif ($message->MsgType == "text") {
                    return 'Todo.' . $data['Content'];
                }
                return $next($message);
            });
        }

        return $server->serve();
    }
}