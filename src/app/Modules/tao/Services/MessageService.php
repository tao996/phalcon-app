<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\sdk\aliyun\AliyumEmailDriver;
use App\Modules\tao\sdk\aliyun\AliyumSmsDriver;
use App\Modules\tao\sdk\EmailDriverInterface;
use App\Modules\tao\sdk\SmsDriverInterface;
use App\Modules\tao\Services\Mock\EmailMockDriver;
use App\Modules\tao\Services\Mock\SmsMockDriver;
use Phax\Traits\Singleton;

class MessageService
{

    use Singleton;

    private readonly array $config;

    protected function __construct()
    {
        $this->config = ConfigService::groupRows('sms');
    }

    private string $smsEngine = '';

    public function sms(): SmsDriverInterface
    {

        if ((int)$this->config['sms_mock'] > 0) {
            $this->smsEngine = 'mock';
            return new SmsMockDriver((int)$this->config['sms_mock_result'] > 0);
        }
        if ((int)$this->config['alisms'] > 0) {
            $this->smsEngine = 'ali';
            return new AliyumSmsDriver([
                'accessKeyId' => $this->config['alisms_access_key'],
                'accessKeySecret' => $this->config['alisms_access_secret'],
                'signName' => $this->config['alisms_signname']
            ]);
        }
        throw new \Exception('没有可使用的 SMS 引擎');
    }

    public function getSmsEngine(): string
    {
        return $this->smsEngine;
    }

    private string $emailEngine = '';

    /**
     * @throws \Exception
     */
    public function email(): EmailDriverInterface
    {
        if ((int)$this->config['sms_mock'] > 0) {
            $this->emailEngine = 'mock';
            return new EmailMockDriver((int)$this->config['sms_mock_result'] > 0);
        }
        if ((int)$this->config['aliemail'] > 0) {
            $this->emailEngine = 'ali';
            return new AliyumEmailDriver([
                'accessKeyId' => $this->config['alisms_access_key'],
                'accessKeySecret' => $this->config['alisms_access_secret'],
                'accountName' => $this->config['aliemail_account'],
                'fromAlias' => $this->config['aliemail_fromalias'],
            ]);
        }
        throw new \Exception('没有可使用的邮件引擎');
    }

    public function getEmailEngine(): string
    {
        return $this->emailEngine;
    }
}