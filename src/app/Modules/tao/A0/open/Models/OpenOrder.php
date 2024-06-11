<?php

namespace App\Modules\tao\A0\open\Models;

use App\Modules\tao\BaseModel;
use Phax\Utils\MyHelper;

class OpenOrder extends BaseModel
{
    const StatusCreate = 1; // 待支付
    const StatusNotPay = 5; // 未支付
    const StatusRevoked = 10;// 已撤销（付款码支付）
    const StatusUserPaying = 11;// 用户支付中（付款码支付）
    const StatusSuccess = 20; // 支付成功
    const StatusError = 40; // 支付失败（其它原因，如银行返回失败）
    const StatusRefund = 50; // 转入退款
    const StatusClose = 65; // 已关闭
    const StatusUnknown = 100; // 内部错误

    // https://pay.weixin.qq.com/docs/merchant/apis/jsapi-payment/payment-notice.html
    public const MapText2Status = [
        'SUCCESS' => self::StatusSuccess,
        'REFUND' => self::StatusRefund, // 转入退款
        'NOTPAY' => self::StatusNotPay, // 未支付
        'CLOSED' => self::StatusClose, // 已关闭
        'REVOKED' => self::StatusRevoked, // 已撤销（付款码支付）
        'USERPAYING' => self::StatusUserPaying, // 用户支付中（付款码支付）
        'PAYERROR' => self::StatusError, //支付失败(其他原因，如银行返回失败)
    ];
    const MapStatus = [
        self::StatusCreate => '待支付',
        self::StatusSuccess => '支付成功',
        self::StatusRefund => '已退款',
        self::StatusNotPay => '未支付',
        self::StatusClose => '已关闭',
        self::StatusRevoked => '已撤销',
        self::StatusUserPaying => '支付中',
        self::StatusError => '支付失败',
        self::StatusUnknown => '内部错误'
    ];

    const ChannelWepay = 1; // 微信支付
    const ChannelAlipay = 2; // 支付宝

    const MapChannel = [
        self::ChannelWepay => '微信',
        self::ChannelAlipay => '支付宝'
    ];

    const TradeTypeJsapi = 1;

    const MapTradeType = [
        self::TradeTypeJsapi => 'JSAPI',
    ];


    public int $user_id = 0;
    public int $channel = 0; // 渠道
    public int $trade_type = 0; // 来源

    public string $appid = ''; // 应用 ID
    public string $mchid = ''; // 直连商户号
    public string $openid = '';
    public int $amount = 0; // 订单金额
    public int $currency = 1; // 默认 CNY

    public string $metadata = ''; // 商品信息
    public string $response = ''; // 响应信息
    public int $status = self::StatusCreate;
    public string $rndcode = '1'; // 随机码
    public string $msg = ''; // 提示信息


    public string $transaction_id = '';
    public int $success_time = 0;

    public function beforeCreate()
    {
        $this->rndcode = MyHelper::random(0, 5);
    }

    public const CURRENCY = ['CNY', 'CNY'];

    public function getOutTradeNo(): string
    {
        return join('_', [$this->id, $this->created_at, $this->rndcode]);
    }

    /**
     * 通过订单号查询订单
     * @param string $outTradeNo
     * @return OpenOrder
     * @throws \Exception
     */
    public static function fromOutTradeNo(string $outTradeNo, array $columns = []): OpenOrder
    {
        $data = explode('_', $outTradeNo);
        if (count($data) != 3) {
            throw new \Exception('不符合规划的订单号');
        } else if (intval($data[0]) < 1) {
            throw new \Exception('订单 ID 错误');
        }
        if (empty($columns)) {
            $order = self::findFirst($data[0]);
        } else {
            $record = self::queryBuilder()->int('id', $data[0])
                ->columns(array_merge(['created_at', 'rndcode'], $columns))
                ->findFirst();
            $order = new OpenOrder();
            $order->assign($record);
        }
        if (empty($order)) {
            throw new \Exception('没有找到符合订单号的记录');
        } else if ($order->created_at != $data[1]) {
            throw new \Exception('订单号数据错误 1');
        } else if ($order->rndcode != $data[2]) {
            throw new \Exception('订单号数据错误 2');
        }
        return $order;
    }

    public function getMetadata(): array
    {
        return !!$this->metadata ? json_decode($this->metadata, true) : [];
    }

    public function getResponse(): array
    {
        return !!$this->response ? json_decode($this->response, true) : [];
    }

    public function getPostJsapi(array $merge = []): array
    {
        return array_merge([
            'mchid' => $this->mchid,
            'out_trade_no' => $this->getOutTradeNo(),
            'appid' => $this->appid,
            'amount' => [
                'total' => $this->amount,
                'currency' => self::CURRENCY[$this->currency],
            ],
            'payer' => [
                'openid' => $this->openid
            ]
        ], $merge);
    }

}