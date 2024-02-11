<?php

const PATH_TAO = PATH_APP_MODULES . 'tao/';
const PATH_TAO_A0 = PATH_TAO . 'A0/';
const PATH_TAO_VIEW = PATH_APP_MODULES . 'tao/views/layui/';

if (!function_exists('appURL')) {
    function appURL(string $path): string
    {
        return url($path, false, false);
    }
}