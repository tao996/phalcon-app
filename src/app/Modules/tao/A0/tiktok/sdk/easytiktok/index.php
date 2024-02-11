<?php

\app\Modules\tao\sdk\SdkHelper::easyWechat();

loader()
    ->addNamespace('EasyTiktok',__DIR__.'/src')
    ->register();