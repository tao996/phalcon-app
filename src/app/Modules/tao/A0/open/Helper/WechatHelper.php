<?php

namespace App\Modules\tao\A0\open\Helper;

use App\Modules\tao\A0\open\Services\OpenConfigService;
use App\Modules\tao\sdk\SdkHelper;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Phax\Mvc\Response;
use Phax\Support\Exception\BlankException;

class WechatHelper
{
    /**
     * 是否微信浏览器
     * @return bool
     */
    public static function isMicroMessengerBrowser(): bool
    {
        $ua = request()->getUserAgent();
        return str_contains($ua, 'MicroMessenger');
    }

    /**
     * 直接发送响应信息给微信服务器
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Phalcon\Http\ResponseInterface
     */
    public static function response(\Psr\Http\Message\ResponseInterface $response)
    {
        return Response::send($response->getBody()->getContents(), $response->getStatusCode());
    }

    /**
     * 拼接一个 URL 地址
     * @param string $mca 操作
     * @param array $query 查询参数
     * @return string
     */
    public static function url(string $mca, array $query = [], bool $multi = true): string
    {
        $url = url($mca, false, $multi, $query);
        $origin = OpenConfigService::getWith('proxy_origin', config('app.url'));
        return rtrim($origin, '/') . $url;
    }

    /**
     * 直接输出一个二维码
     * @param string $data 二维码数据
     */
    public static function renderQrcode(string $data)
    {
        SdkHelper::qrcode();

        $qrCode = QrCode::create($data)
            ->setSize(300)->setMargin(10)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        header('Content-Type: ' . $result->getMimeType());
        echo $result->getString();
        throw new BlankException();
    }

    /**
     * 跳转到微信简易授权，主要用于获取用户的 openid
     * @param array $query 查询参数，其中 appid 和 target（内部地址） 是必须的
     * @param bool $jump 是否自动跳转
     * @param bool $qrcode 如果不是微信浏览器，则显示二维码供扫描
     * @return string
     * @throws BlankException
     */
    public static function quickOpenid(array $query = [], bool $jump = true, bool $qrcode = true)
    {
        if (empty($query['appid'])) {
            throw new \Exception('appid should not empty');
        }
        if (empty($query['target'])) {
            throw new \Exception('target should not empty');
        }
        $redirectURL = WechatHelper::url('tao.wechat/auth', $query);
        if ($qrcode && !self::isMicroMessengerBrowser()) {
            self::renderQrcode($redirectURL);
        }
        if ($jump) {
            header("Location:{$redirectURL}");
            throw new BlankException();
        }
        return $redirectURL;
    }
}