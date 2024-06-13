<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\Models\SystemUploadfile;
use App\Modules\tao\sdk\aliyun\oss\AliyunDriver;
use App\Modules\tao\sdk\OssDriverInterface;
use App\Modules\tao\sdk\qiniu\QiniuDriver;
use App\Modules\tao\sdk\tencent\cos\QcloudDriver;
use App\Modules\tao\Services\ConfigService;
use OSS\Core\OssException;
use Phax\Support\Config;
use Phax\Utils\MyHelper;

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
        // 上传到当前项目目录
        $subDir = 'upload/' . Config::currentProject('phax') . '/' . date('ymd') . '/';
        $pathUploadDir = MyHelper::dirSeparator(PATH_PUBLIC . $subDir);
        if (!file_exists($pathUploadDir)) {
            mkdir($pathUploadDir, 0777, true);
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
                'width' => $width,
                'height' => $height, // 尺寸
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
     * @param string $dir 保存的目录
     * @param bool $rmLocal 是否移除本地文件
     * @return SystemUploadfile
     * @throws \Exception
     */
    private function ossUpload(OssDriverInterface $driver, string $dir, bool $rmLocal = true): SystemUploadfile
    {
        $f = $this->moveToLocal(); // 保存到本地
        $names = [$dir];
        $names[] = basename($f->url);
        $objectName = join('/', $names);
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

    private function getOssDriver(string $driver, array $config): OssDriverInterface
    {
        switch ($driver) {
            case 'alioss':
                return new AliyunDriver([
                    'alioss_access_key_id' => $config['alioss_access_key_id'],
                    'alioss_access_key_secret' => $config['alioss_access_key_secret'],
                    'alioss_endpoint' => $config['alioss_endpoint'],
                    'alioss_bucket' => $config['alioss_bucket'],
                    'alioss_domain' => $config['alioss_domain'],
                ]);
            case 'qnoss': // 七牛云
                return new QiniuDriver([
                    'qnoss_access_key' => $config['qnoss_access_key'],
                    'qnoss_secret_key' => $config['qnoss_secret_key'],
                    'qnoss_bucket' => $config['qnoss_bucket'],
                    'qnoss_domain' => $config['qnoss_domain'],
                ]);
            case 'txcos': // 腾讯云
                return new QcloudDriver([
                    'txcos_secret_id' => $config['txcos_secret_id'],
                    'txcos_secret_key' => $config['txcos_secret_key'],
                    'txcos_region' => $config['txcos_region'],
                    'txcos_bucket' => $config['txcos_bucket'],
                    'schema' => 'https',
                ]);
            default:
                throw new \Exception('不支持的云上传类型');
        }
    }

    /**
     * @throws OssException
     * @throws \Exception
     */
    public function save(\Phalcon\Http\Request\File $file = null): SystemUploadfile
    {
        $this->mustGetFile($file);
        switch ($this->getUploadType()) {
            case 'def': // 系统默认配置
                $uploadcc = \config('tao.upload')?->toArray();
                if ($uploadcc) {
                    if (empty($uploadcc['driver'])) {
                        throw new \Exception('系统未配置默认上传');
                    }
                    $this->_config['upload_type'] = $uploadcc['driver'];
                    $oss = $this->getOssDriver($uploadcc['driver'], $uploadcc[$uploadcc['driver']]);
                    return $this->ossUpload($oss, Config::currentProject('phax'));
                } else {
                    // 退回到本地上传
                    $this->_config['upload_type'] = 'local';
                    return $this->moveToLocal();
                }
            case 'local':
                return $this->moveToLocal();
            default:
                $oss = $this->getOssDriver($this->getUploadType(), $this->_config);
                return $this->ossUpload($oss, $this->_config['oss_dir']);
        }
    }

    /**
     * 服务端签名直传
     */
    public function serverToken()
    {
    }

}