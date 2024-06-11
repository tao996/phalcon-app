<?php

namespace Phax\Utils;

class MyFileSystem
{
    /**
     * 跳过 . 和 .. 文件名
     * @param $name
     * @return bool
     */
    public static function excludeFileNames($name): bool
    {
        return in_array($name, ['.', '..']);
    }

    /**
     * 获取子目录（不含文件）
     * @param string $parentDir
     * @param string $type dir 目录；file 文件；默认为空，表示文件和目录
     * @return array
     */
    public static function findInDirs(string $parentDir, string $type = ''): array
    {
        $rows = [];
        if (is_dir($parentDir)) {
            foreach (scandir($parentDir) as $name) {
                if (!self::excludeFileNames($name)) {
                    if ('' === $type
                        || ('dir' === $type && is_dir($parentDir . '/' . $name))
                        || ('file' === $type && is_file($parentDir . '/' . $name))
                    ) {
                        $rows[] = $name;
                    }
                }
            }
        }
        return $rows;
    }
}