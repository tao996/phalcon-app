<?php

namespace Phaxui;

class Vue
{
    public static string $version = '3.3.9';

    public static function js()
    {
        if (HtmlAssets::$cdnNcn) {
            echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/'.self::$version.'/vue.global.prod.min.js"></script>';
        } elseif (HtmlAssets::$cdnCN) {
            echo '<script src="https://cdn.staticfile.org/vue/'.self::$version.'/vue.global.prod.min.js"></script>';
        } else {
            echo '<script src="/assets/vue/vue@3.3.6.js"></script>';
        }
    }
}