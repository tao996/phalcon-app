<?php

namespace app\Modules\tao\sdk;

interface OssDriverInterface
{
    public function uploadFile(string $objectName, string $filePath);
}