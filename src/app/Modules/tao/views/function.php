<?php
/**
 * 与模板数据比较
 * @param string $path 路径 或者 值
 * @param mixed $output 输出的内容，如果提供，则会直接使用 echo
 * @param mixed $cmpValue 待比较的值，默认为 1
 * @param bool $pathIsValue $path 是否为值
 * @return mixed
 */
function viewDataCompare(string $path, mixed $output = "", mixed $cmpValue = 1, bool $pathIsValue = false): mixed
{
    $int = is_int($cmpValue);
    $rst = ($pathIsValue ? $path : viewData($path, $int ? 0 : '')) == $cmpValue;
    if ($output) {
        return $rst ? $output : '';
    }
    return $rst;
}

/**
 * 通常用于将 php 变量转为 js 布尔值
 * @param bool $condition
 * @return string
 */
function viewDataBool2String(bool $condition): string
{
    return $condition ? 'true' : 'false';
}

/**
 * 读取系统配置的值
 * @param string $path 路径
 * @param mixed $default 默认值
 * @return mixed|string
 */
function systemConfig(string $path, mixed $default = ''): mixed
{
    return \App\Modules\tao\Services\ConfigService::getWith($path, $default);
}

function systemConfigCompare(string $path, string $output, $cmpValue = "1"): void
{
    echo \App\Modules\tao\Services\ConfigService::getWith($path, "") == $cmpValue ? $output : '';
}

/**
 * 模板目录
 * @return string
 */
function viewsDir(): string
{
    return view()->getViewsDir();
}
