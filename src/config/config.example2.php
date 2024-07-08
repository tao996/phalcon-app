<?php
$data = require_once __DIR__ . '/config.example.php';

$data['app'] = array_merge($data['app'], [
    'demo' => true,
    'error' => 'App\Modules\tao\BaseController',
    'cdn' => '', // cn 国内使用| ncn 国外使用 |(your cdn domain, exam:https://test.com)
    'project' => [
        'sites' => [
            'city' => ['city.test'],
            'boyu' => ['boyu.test'],
            'taoci' => ['taoci.test'],
        ],
        'default' => 'taoci',
        'config_prefix' => true,
    ]
]);
// for local development
// $data['metadata']['driver'] = 'memory';

// src/app/Modules/tao 的配置
$data['tao'] = [
    'upload' => [
        'driver' => 'qnoss',
        'qnoss' => [ // 七牛云
            'qnoss_access_key' => '', // 访问密钥 AccessKey
            'qnoss_secret_key' => '', // 安全密钥 SecretKey
            'qnoss_bucket' => '', // 存储空间
            'qnoss_domain' => '', // 访问域名
        ],
        'alios' => [
            'alioss_access_key_id' => '', // 公钥
            'alioss_access_key_secret' => '', // 私钥
            'alioss_endpoint' => '', // 数据中心
            'alioss_bucket' => '', // 空间名称
            'alioss_domain' => '', // 访问域名
        ],
        'txcos' => [
            'txcos_secret_id' => '', // 密钥
            'txcos_secret_key' => '', // 私钥
            'txcos_region' => '', // 存储桶地域
            'txcos_bucket' => '', //
            'schema' => 'https',
        ]
    ],
    'kefu'=>[], // 客服
];