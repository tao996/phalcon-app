<?php

namespace Phaxui;

class Vue
{
    public static function js()
    {
        if (HtmlAssets::$cdnJs) {
            echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.3.9/vue.global.prod.min.js"></script>';
        } elseif (HtmlAssets::$cdn) {
            echo '<script src="https://cdn.staticfile.org/vue/3.3.9/vue.global.prod.min.js"></script>';
        } else {
            echo '<script src="/assets/vue/vue@3.3.6.js"></script>';
        }
    }
}