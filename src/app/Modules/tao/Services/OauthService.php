<?php

namespace app\Modules\tao\Services;

use app\Modules\tao\sdk\phaxui\helper\Html;
use Phax\Traits\Singleton;

class OauthService
{
    use Singleton;

    public array $cache = [];

    private function __construct()
    {
        $this->cache = \app\Modules\tao\Services\ConfigService::groupRows('oauth');
    }


    public function accountPlaceholder(): string
    {
        return Html::placeholderMerge([
            '手机号' => $this->supportCnPhone(),
            '电子邮箱' => $this->supportEmail(),
        ]);
    }

    public function supportCnPhone(): bool
    {
        return ConfigService::activeValue($this->cache['cn_phone']);
    }

    public function supportEmail(): bool
    {
        return ConfigService::activeValue($this->cache['email']);
    }

    public function supportRegister(): bool
    {
        return ConfigService::activeValue($this->cache['register']);
    }

    public function supportGoogle(): bool
    {
        return ConfigService::activeValue($this->cache['google_oauth']);
    }

    public function googleProvider():array {
        return ['enabled' => $this->supportGoogle(), 'keys' => [
            'id' => $this->cache['google_client_id'],
            'secret' => $this->cache['google_client_secret']
        ]];
    }
}