<?php

namespace app\Modules\tao\Helper;

use app\Modules\tao\Models\SystemUploadfile;
use app\Modules\tao\sdk\aliyun\oss\AliyunDriver;
use app\Modules\tao\sdk\OssDriverInterface;
use app\Modules\tao\sdk\qiniu\QiniuDriver;
use app\Modules\tao\sdk\tencent\cos\QcloudDriver;
use app\Modules\tao\Services\ConfigService;
use OSS\Core\OssException;
use Phax\Support\Facades\Helper;

/**
 * 上传组件
 */
class FileUpload
{
    private array $_config;
    private \Phalcon\Http\Request\File $_file;
    private array $_options = [
        'hash' => true, // 使用文件 hash 命名
    ];

    public function __construct()
    {
        $this->_config = ConfigService::groupRows('upload');
//        dd($this->_config);
    }

    /**
     * 對上傳來源進行校驗
     * @return $this
     * @throws \Exception
     */
    public function fromRequest()
    {
        if (!request()->isPost()) {
            throw new \Exception('非法请求');
        }
        if (!request()->hasFiles()) {
            throw new \Exception('必须指定上传文件');
        }
        return $this;
    }

    private function mustGetFile(\Phalcon\Http\Request\File $file = null)
    {
        if (is_null($file)) {
            if (empty($this->_file)) {
                $this->_file = request()->getUploadedFiles()[0];
            }
        } else {
            $this->_file = $file;
        }
        if (empty($this->_file)) {
            throw new \Exception('必须指定上传文件');
        }
        return $this->_file;
    }

    public function getUploadType(): string
    {
        return $this->_config['upload_type'];
    }

    public function getFile(): \Phalcon\Http\Request\File
    {
        return $this->_file;
    }


    /**
     * 验证图片
     * @param \Phalcon\Http\Request\File $file
     * @throws \Exception
     */
    public function validate(\Phalcon\Http\Request\File $file = null): self
    {
        $file = $this->mustGetFile($file);
        if (!in_array($file->getExtension(), explode(',', $this->_config['upload_allow_ext']))) {
//            dd('checkType',$file->getType(),$this->_config['upload_allow_mime']);
            throw new \Exception('不允许上传的指定文件类型');
        }
        if (!in_array($file->getExtension(), explode(',', $this->_config['upload_allow_ext']))) {
            throw new \Exception('不允许上传的文件');
        }

        $bitSize = $this->getMaxBytes();
        if ($file->getSize() > $bitSize) {
            throw new \Exception('文件超过了' . Format::humanFileSize($bitSize));
        }
        return $this;
    }

    private function getMaxBytes(): int
    {
        // 单位是 m
        $size = intval($this->_config['upload_allow_size']);
        return ($size > 0 ? $size : 5) * 1048576; // 1024 * 1024
    }

    /**
     * 将文件保存到本地
     * @return SystemUploadfile
     * @throws \Exception
     */
    private function moveToLocal(): SystemUploadfile
    {
        $subDir = 'upload/' . date('ymd') . '/';
        $pathUploadDir = Helper::dirSeparator(PATH_PUBLIC . $subDir);
        if (!file_exists($pathUploadDir)) {
            mkdir($pathUploadDir);
        }
        $sha1 = md5_file($this->_file->getTempName());
        $saveName = $this->_file->getName(); // 保存的文件名
        if ($this->_options['hash']) {
            $saveName = $sha1 . '.' . strtolower($this->_file->getExtension());
        }

        if ($this->_file->moveTo($pathUploadDir . $saveName)) {
            list($width, $height) = getimagesize($pathUploadDir . $saveName);
            $sf = new SystemUploadfile();
            $sf->assign([
                'upload_type' => $this->getUploadType(), // 文件类型
                'summary' => $this->_file->getName(), // 原始文件名
                'url' => '/' . $subDir . $saveName, // 本地访问链接地址(添加 config('app.url') . 可能会导致移除数据库时无法访问）
                'width' => $width, 'height' => $height, // 尺寸
                'mime_type' => $this->_file->getType(), // mime 类型
                'file_size' => $this->_file->getSize(), // 文件大小
                'file_ext' => $this->_file->getExtension(), // 文件扩展名
                'sha1' => $sha1, // 文件 hash 值
            ]);
            $sf->tmpSavePath = $pathUploadDir . $saveName;
            return $sf;
        }
        throw new \Exception('保存本地文件错误');
    }


    /**
     * 将上传到本地的文件再次上传到云
     * @param OssDriverInterface $driver
     * @param bool $rmLocal 是否移除本地文件
     * @return SystemUploadfile
     * @throws \Exception
     */
    private function ossUpload(OssDriverInterface $driver, bool $rmLocal = true): SystemUploadfile
    {
        $f = $this->moveToLocal(); // 保存到本地
        $objectName = join('/', [$this->_config['oss_dir'], basename($f->url)]);
        $f->url = $driver->uploadFile($objectName, $f->tmpSavePath);
        if ($rmLocal) {
            if (file_exists($f->tmpSavePath)) {// 移除本地文件
                if (unlink($f->tmpSavePath)) {
                    $f->tmpSavePath = '';
                }
            }
        }
        return $f;
    }

    /**
     * @throws OssException
     * @throws \Exception
     */
    public function save(\Phalcon\Http\Request\File $file = null): SystemUploadfile
    {
        $this->mustGetFile($file);
        switch ($this->_config['upload_type']) {
            case 'local':
                return $this->moveToLocal();
            case 'alioss':
                $alioss = new AliyunDriver([
                    'alioss_access_key_id' => $this->_config['alioss_access_key_id'],
                    'alioss_access_key_secret' => $this->_config['alioss_access_key_secret'],
                    'alioss_endpoint' => $this->_config['alioss_endpoint'],
                    'alioss_bucket' => $this->_config['alioss_bucket'],
                    'alioss_domain' => $this->_config['alioss_domain'],
                ]);
                return $this->ossUpload($alioss);
            case 'qnoss': // 七牛云
                $qiniu = new QiniuDriver([
                    'qnoss_access_key' => $this->_config['qnoss_access_key'],
                    'qnoss_secret_key' => $this->_config['qnoss_secret_key'],
                    'qnoss_bucket' => $this->_config['qnoss_bucket'],
                    'qnoss_domain' => $this->_config['qnoss_domain'],
                ]);
                return $this->ossUpload($qiniu);
            case 'txcos': // 腾讯云
                $tcloud = new QcloudDriver([
                    'txcos_secret_id' => $this->_config['txcos_secret_id'],
                    'txcos_secret_key' => $this->_config['txcos_secret_key'],
                    'txcos_region' => $this->_config['txcos_region'],
                    'txcos_bucket' => $this->_config['txcos_bucket'],
                    'schema' => 'https',
                ]);
                return $this->ossUpload($tcloud);
            default:
                throw new \Exception('未知的上传引擎');
        }
    }

    /**
     * 服务端签名直传
     */
    public function serverToken()
    {

    }

}