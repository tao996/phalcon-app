<?php

namespace App\Modules\tao\sdk\qiniu;

use App\Modules\tao\sdk\OssDriverInterface;
use Phax\Utils\MyData;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

/**
 * @link https://github.com/qiniu/php-sdk/releases
 */
class QiniuDriver implements OssDriverInterface
{
    protected string $accessKey = '';
    protected string $secretKey = '';
    protected string $bucket = '';
    protected string $domain = '';
    protected \Qiniu\Auth $auth;

    public function __construct(array $config)
    {
        require_once __DIR__ . '/qiniu.phar';

        MyData::mustHasSet($config, ['qnoss_access_key', 'qnoss_secret_key', 'qnoss_bucket', 'qnoss_domain']);

        $this->accessKey = $config['qnoss_access_key'];
        $this->secretKey = $config['qnoss_secret_key'];
        $this->bucket = $config['qnoss_bucket'];
        $this->domain = $config['qnoss_domain'];

        $this->auth = new Auth($this->accessKey, $this->secretKey);
    }

    /**
     * 文件上传
     * @link https://developer.qiniu.com/kodo/1241/php
     * @param string $objectName 上传到存储后保存的文件名
     * @param string $filePath 要上传文件的本地路径
     * @return string
     * @throws \Exception
     */
    public function uploadFile(string $objectName, string $filePath)
    {
        $token = $this->auth->uploadToken($this->bucket);
        $uploadMgr = new UploadManager();
        list($result, $error) = $uploadMgr->putFile($token, $objectName, $filePath);
        if ($error !== null) {
            throw new \Exception('上传七牛云文件保存失败:' . $error);
        }
        return $this->domain . '/' . $result['key'];
    }

    public function imageToken(string $bucket = ''):string
    {
        // https://developer.qiniu.com/kodo/1206/put-policy
        return $this->auth->uploadToken($bucket ?: $this->bucket,null,7200,[
            'mimeLimit'=>'image/*'
        ]);
    }

    public function getDomain()
    {
        return $this->domain;
    }
}