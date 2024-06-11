<?php

namespace App\Modules\tao\A0\wechat\Controllers\demo;

use App\Modules\tao\A0\open\Helper\WechatHelper;
use App\Modules\tao\A0\open\Models\OpenOrder;
use App\Modules\tao\A0\open\Services\OpenUserService;
use App\Modules\tao\A0\open\Services\WechatApplicationService;
use App\Modules\tao\A0\wechat\Services\WechatPayService;
use App\Modules\tao\BaseController;
use App\Modules\tao\Services\LoginService;
use EasyWeChat\Pay\Message;
use Phax\Mvc\Request;
use Phax\Mvc\Response;
use Phax\Support\Exception\BlankException;
use Phax\Support\Logger;

class PayController extends BaseController
{
    protected array|string $openActions = '*';
    public bool $disableUpdateActions = true;

    /**
     * 一个简单的微信支付测试
     * http://localhost:8071/m/tao.wechat/demo.pay
     */
    public function indexAction()
    {

        $appid = $this->request->getQuery('appid', null, WechatPayService::getPayAppid());
        if (empty($appid)) {
            dd('必须指定支付公众号 ID');
        }
        if (LoginService::tryLogin()) {
            $user = LoginService::getLoginUser();
            if ($openid = OpenUserService::getOpenidByUserId($appid, $user->id)) {
                $redirectURL = WechatHelper::url('tao.wechat/demo.pay/jsapi', ['openid' => $openid, 'appid' => $appid], true);
                header("Location:{$redirectURL}");
                throw new BlankException();
            }
        }
        WechatHelper::quickOpenid([
            'appid' => $appid,
            'target' => url('tao.wechat/demo.pay/jsapi')
        ]);
    }

    /**
     * jsapi 支付
     * @link http://localhost:8071/m/tao.wechat/demo.pay/jsapi?openid=xxx&appid=xxx
     * @link [JSAPI 调起支付]https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=7_7&index=6
     * @link [JSAPI 下单]https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_1.shtml
     * @link [获取 openid]https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=4_4
     */
    public function jsapiAction()
    {


        if (!WechatHelper::isMicroMessengerBrowser()) {
            dd('只支持在微信浏览器中操作');
        }
        $appid = $this->request->getQuery('appid', 'string');
        $openid = $this->request->getQuery('openid', 'string');

        if ($this->request->isPost()) {
            $data = Request::getData();
            $money = isset($data['money']) ? (float)$data['money'] * 100 : 1; // 默认 1分



            $app = WechatApplicationService::getPayApplication($appid);
            $metadata = ['description' => 'WeTest Pay'];
            $order = new OpenOrder();
            $order->assign([
                'appid' => $appid,
                'channel' => OpenOrder::ChannelWepay,
                'trade_type' => OpenOrder::TradeTypeJsapi,
                'mchid' => $app->getMerchant()->getMerchantId(),
                'openid' => $openid,
                'amount' => $money,
                'metadata' => json_encode($metadata)
            ]);
            if (!$order->create()) {
                return $this->error($order->getFirstError());
            }

            $jsConfig = WechatPayService::wrapJsapi($order->getPostJsapi([
                'description' => $metadata['description'],
                'notify_url' => WechatHelper::url('tao.wechat/demo.pay/notify/' . $appid)
            ]), $app);

            return $this->success('', $jsConfig);

        }
        return [];
    }

    /**
     * 支付通知
     * @param string $appid 公众号
     */
    public function notifyAction(string $appid = '')
    {
        $this->autoResponse = false;

        $appid = $appid ?: WechatPayService::getPayAppid();
        if (empty($appid)) {
            // 已经无法处理，以后使用订单查询处理
            return Response::send(['code' => 'SUCCESS', 'message' => '成功']);
        }
        $app = WechatApplicationService::getPayApplication($appid);
        $server = $app->getServer();
        // https://easywechat.com/6.x/pay/index.html#签名验证
        $server->handlePaid(function (Message $message, \Closure $next) use ($app) {
            $data = $message->toArray();
            Logger::info($data); // 记录响应信息到日志中

            $order = OpenOrder::fromOutTradeNo($message->out_trade_no, ['id', 'status']);

            if ($order->status == OpenOrder::StatusCreate) {
                try {
                    $app->getValidator()->validate($app->getRequest());
                    $order->status = OpenOrder::StatusSuccess;
                    $order->transaction_id = $data['transaction_id'];
                    $order->success_time = strtotime($data['success_time']);
                    $columns = ['status', 'transaction_id', 'success_time'];
                } catch (\Exception $e) {
                    $order->msg = '订单验证失败';
                    $order->status = OpenOrder::StatusUnknown;
                    Logger::info($order->msg, $e->getMessage());
                    $columns = ['status', 'msg'];
                }
                if (!$order->updateColumns($columns)) {
                    Logger::info('保存订单数据错误', $order->getFirstError());
                }
            }
            return $next($message);
        });
        return WechatHelper::response($server->serve());
    }
}