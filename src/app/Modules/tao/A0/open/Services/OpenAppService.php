<?php

namespace App\Modules\tao\A0\open\Services;

use App\Modules\tao\A0\open\Helper\CertSecretHelper;
use App\Modules\tao\A0\open\Models\OpenApp;
use App\Modules\tao\A0\wechat\Services\WechatPayService;

class OpenAppService
{
    private const cacheKey = 'tao_open_app';

    public static function rows()
    {
        if (cache()->has(self::cacheKey)) {
            return (array)cache()->get(self::cacheKey);
        }
        return self::cache();
    }

    public static function getWithAppid(string $appid, bool $must = true)
    {
        $data = IS_DEBUG ? self::cache() : self::rows();
        if ($must && !isset($data[$appid])) {
            throw new \Exception('找不到指定的 app');
        }
        return (array)$data[$appid] ?? null;
    }

    public static function cache(): array
    {
        $rows = OpenApp::queryBuilder()
            ->int('status', 1)
            ->findColumn([], 'appid');
        if (!cache()->set(self::cacheKey, $rows)) {
            logger()->error('cache tao.open.app failed:' . __CLASS__);
        }
        return $rows;
    }

    public static function getWith($appid)
    {
        $data = self::rows();
        if (isset($data[$appid])) {
            return (array)$data[$appid];
        }
        throw new \Exception('没有找到(' . $appid . ')的应用配置');
    }

    public static function kindCompare(string $appid, string $kind): bool
    {
        $wc = self::getWith($appid);
        switch ($kind) {
            case 'mini':
                return OpenApp::isMini($wc['kind']);
            case 'gzh':
                return OpenApp::isGzh($wc['kind']);
            case 'dyh':
                return $wc['kind'] == 'dyh';
            case 'fwh':
                return $wc['kind'] == 'fwh';
            case 'web':
                return OpenApp::isWeb($wc['kind']);
            case 'work':
                return OpenApp::isWork($wc['kind']);
            default:
                throw new \Exception('kind value is not allow:' . $kind);
        }
    }

    /**
     * 加密字段，并保存模型数据
     * @param OpenApp $app
     * @param string $name public_key|rsa_public_key|rsa_private_key
     * @param string $content 证书内容
     * @return boolean
     */
    public static function encrypt(OpenApp $app, string $name, string $content): bool
    {
        $pIndexName = OpenApp::getPIndex($name);
        if (strlen($content) < 100) {
            throw new \Exception('证书内容过短或不符合规范？');
        }
        $fMd5 = md5($content);
        $pIndex = rand(30, 80);
        $newContent = CertSecretHelper::encryptData($content, $pIndex, 5);

        $app->assign([
            $name => $fMd5, $pIndexName => $pIndex,
        ]);
        if ($app->save()) {
            $dir = WechatPayService::pathCertDir();
            if (!file_put_contents($dir . $fMd5, $newContent)) {
                throw new \Exception('app 保存证书失败');
            }
            return true;
        }
        return false;
    }

    /**
     * 还原证书内容（交易系统专用）
     * @param string $filename 文件名称，来自 TiktokApp 中的 public_key|rsa_public_key|rsa_private_key 内容
     * @param int $pIndex 来自 TiktokApp 中的 pi0|pi1|pi2
     * @return string 解密内容
     * @throws \Exception
     */
    public static function decrypt(string $filename, int $pIndex)
    {
        if (empty($filename)) {
            throw new \Exception('tiktok 证书文件名不能为空');
        } elseif ($pIndex < 1) {
            throw new \Exception('tiktok 证书加密索引不能为空');
        }
        $file = WechatPayService::pathCertDir() . $filename;
        if (!file_exists($file)) {
            throw new \Exception('tiktok 证书不存在');
        }
        $content = file_get_contents($file);
        return CertSecretHelper::decryptData($content, $pIndex, 5);
    }
}