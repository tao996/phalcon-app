<?php

namespace Phaxui;

class Vue
{
    public static string $version = '3.3.9';

    public static function js()
    {
        echo '<script src="' . HtmlAssets::$cdn . 'vue/' . self::$version . '/vue.global.prod.min.js"></script>';
    }
}