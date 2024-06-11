<?php

namespace Phax\Utils;

/**
 * 数据格式化
 * @link https://www.php.net/manual/zh/ref.array.php
 */
class MyData
{
    /**
     * 以路径方式来查询数组中的值
     * @param array $data 待查询的数组
     * @param string $path 多层次使用 . 来分开，示例 a.b.c
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function findWithPath(array &$data, string $path, mixed $default): mixed
    {
        $keys = explode('.', $path);
        if (count($keys) == 1) {
            return isset($data[$path]) && $data[$path] ? $data[$path] : $default;
        }
        $current = $data;
        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                return $default;
            }
            $current = $current[$key];
        }
        return $current ?: $default;
    }

    /**
     * 使用指定键的傎来代替默认的索引
     * @param array $data
     * @param string|null|int $key 指定列名
     * @return array
     */
    public static function resetRowsKey(array $data, string|null|int $key): array
    {
        if ($key == "") {
            return $data;
        }
        if (empty($data)) {
            return [];
        }
        $rows = [];
        foreach ($data as $item) {
            $rows[$item[$key]] = (array)$item;
//            dd($key, $item[$key],$rows);
        }
        return $rows;
    }

    /**
     * 獲取數據
     * @param array $data 數組
     * @param string|int|null $key 鍵
     * @param mixed $defValue 默認值
     * @return array|mixed|string
     */
    public static function get(array &$data, string|int|null $key, mixed $defValue = ''): mixed
    {
        return $key ? ($data[$key] ?? $defValue) : $data;
    }


    public static function getString(array &$data, string $key, $def = ''): string
    {
        return empty($data[$key]) ? $def : $data[$key];
    }

    public static function getInt(array &$data, string $key, $def = 0): int
    {
        return isset($data[$key]) ? intval($data[$key]) : $def;
    }

    /**
     * 获取整数数组
     * @param array $data 待检测数组 ['ids'=>['1','2','3']] 或者 ['ids'=>[1=>'on',2=>'on',3=>'on']] 或者 ['ids'=>'1,2,3']
     * @param string $key 数组中的键 ids
     * @return array [1,2,3]
     */
    public static function getIntsWith(array &$data, string $key): array
    {
        if (empty($data[$key])) {
            return [];
        }
        return self::getInts($data[$key]);
    }

    /**
     * 获取整数数组，如果不需要重复，可在获取结果后使用 array_unique 进行过滤
     * @param array|string $data ['1','2','3'] 或者 '1,2,3' 或者 [1=>'on',2=>'on',3=>'on']
     * @return array [1,2,3]
     * @throws \Exception
     */
    public static function getInts($data): array
    {
        if (is_string($data)) {
            $items = explode(',', $data);
        } elseif (is_array($data)) {
            $valueInValue = isset($data[0]) && is_numeric($data[0]); // [1,2,3,] 格式
            if ($valueInValue) {
                $items = array_map('intval', $data);
            } else {
                $items = array_keys($data);
            }
        } else {
            throw new \Exception('unsupported params in MyData.getInts');
        }
        $rows = [];
        foreach ($items as $item) {
            if (filter_var($item, \FILTER_VALIDATE_INT)) {
                $rows[] = intval($item);
            } else {
                throw new \Exception($item . ' is not a int value');
            }
        }
        return $rows;
    }

    /**
     * 获取布尔值 : 字符串 (on|true|t|ok), >0 都将被作为 true 对待
     * @param array $data
     * @param string $key
     * @param bool $strict 是否严格类型，只接受 true/false
     * @return bool
     */
    public static function getBool(array $data, string $key, bool $strict = false): bool
    {
        $v = $data[$key] ?? false;
        return self::isBool($v, $strict);
    }

    /**
     * 判断是否为布尔值
     * @param mixed $v 待判断的值
     * @param bool $strict 是否为严格类型，只接受 true/false
     * @return bool
     */
    public static function isBool($v, bool $strict = false): bool
    {
        if ($strict || is_bool($v)) {
            return $v == true;
        }
        if (is_numeric($v)) {
            return intval($v) > 0;
        }
        return in_array(strtolower($v), ['on', 'true', 't', 'ok']);
    }

    public static function notEmpty(array $data, string $key): bool
    {
        return !empty($data[$key]);
    }

    /**
     * @throws \Exception
     */
    public static function mustHasSet(array $data, array|string $keys, array $allowEmpty = []): void
    {
        $allNotAllowEmpty = empty($allowEmpty);
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \Exception($key . ' is not exits in the data when mustHasSet');
            }
            if ($allNotAllowEmpty) {
                if (empty($data[$key])) {
                    throw new \Exception($key . ' is not allow empty when mustHasSet');
                }
            } else {
                if (!in_array($key, $allowEmpty) && empty($data[$key])) {
                    throw new \Exception($key . ' is not allow empty when mustHasSet');
                }
            }
        }
    }

    /**
     * 获取数组中指定键的值（see test）
     * @param array|null $data 原始数组 ['a' => 1, 'b' => 2, 'c' => 'hello']
     * @param array $keys 需要提取的键 ['a', 'c']
     * @return array 由 $keys 组成的新数组 ['a' => 1, 'c' => 'hello']
     */
    public static function getByKeys(array|null &$data, array $keys): array
    {
        if (empty($data)) {
            return [];
        } elseif (empty($keys)) {
            return $data;
        }
        // array_flip 交换数组的键和值
        // array_intersect_key 使用键名计算数组的交集
        return array_intersect_key($data, array_flip($keys));
    }

    public static function picker(array|null &$data, array $keys): array
    {
        return self::getByKeys($data, $keys);
    }

    /**
     * 获取第一个不为空的值
     * @param array $args
     * @param mixed $def 默认值
     * @return mixed
     */
    public static function firstValue(array $args, mixed $def): mixed
    {
        foreach ($args as $arg) {
            if (!empty($arg)) {
                return $arg;
            }
        }
        return $def;
    }

    /**
     * 文本截取
     * @link https://stackoverflow.com/questions/11434091/add-if-string-is-too-long-php
     * @param string $text
     * @param int $length
     * @return string
     */
    public static function subtext(string $text, int $length): string
    {
        if (mb_strlen($text, 'utf8') > $length) {
            return mb_substr($text, 0, $length, 'utf8') . '...';
        } else {
            return $text;
        }

    }

    /**
     * 格式化命名
     * @param string $name refreshNode, refresh-node, refresh_node, RefreshNode
     * @param bool $lcfirst 首字母是否小写，默认是
     * @return string refreshNode
     */
    public static function formatName(string $name, bool $lcfirst = true): string
    {
        $name = str_replace(['-', '_', ' '], '-', $name);
        if (str_contains($name, '-')) {
            $name = MyHelper::camelize($name, '-', true);
        }
        return $lcfirst ? lcfirst($name) : $name;
    }

    /**
     * 只获取指定 keys 的值 <pre>
     *  $items = [ ['a' => 1, 'b' => 2, 'd' => 5], ['a' => 0, 'c' => 5]];
     *  $keys = ['a', 'b', 'c']; $notEmptyKeys = ['a'];
     *  $result1 = [['a' => 1, 'b' => 2]];
     *  $keys = ['a', 'b', 'c']; $notEmptyKeys = [];
     *  $result2 = [['a' => 1, 'b' => 2],['a' => 0, 'b' => 5]];
     * </pre>
     * @param array $items
     * @param array $keys
     * @param array $notEmptyKeys
     * @return array
     */
    public static function findByKeys(array $items, array $keys, array $notEmptyKeys = []): array
    {
        $rows = [];
        foreach ($items as $item) {
            $append = true;
            foreach ($notEmptyKeys as $key) {
                if (empty($item[$key])) {
                    $append = false;
                    break;
                }
            }
            if ($append) {
                $rows[] = self::getByKeys($item, $keys);
            }
        }
        unset($item);
        return $rows;
    }

    /**
     * 切割 textarea 内容
     * @link https://stackoverflow.com/questions/7058168/explode-textarea-php-at-new-lines
     * @param string $content
     * @return array
     */
    public static function splitLine(string $content): array
    {
        return preg_split('/\r\n|[\r\n]/', $content);
    }

    /**
     * 切割空格
     * @link https://stackoverflow.com/questions/1792950/explode-string-by-one-or-more-spaces-or-tabs
     * @param string $content
     * @return array
     */
    public static function splitSpace(string $content): array
    {
        return preg_split('/\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * 将时间转为 yyyymmdd 的整数
     * @param string $dt 时间字符串
     * @return int
     */
    public static function intYmdDate(string $dt): int
    {
        return (int)date('Ymd', strtotime($dt));
    }

    /**
     * 通常用在 Model 中，用于將 int/string 转换；场景：后端 1 发送给前端 active，或者將前端提交的 active 转为后端的 1
     * @param array $mapData [1=>'active', 2=>'disabled',...]
     * @param int|string|null $key 用戶提交的數據
     * @return mixed
     */
    public static function getMapData(array &$mapData, int|null|string $key = 0, bool $reverse = false): mixed
    {
        if (empty($key)) {
            return $reverse ? array_flip($mapData) : $mapData;
        } elseif ($reverse) {
            return array_flip($mapData)[$key] ?? (is_int($key) ? '' : 0);
        }
        return $mapData[$key] ?? (is_int($key) ? 0 : '');
    }

}