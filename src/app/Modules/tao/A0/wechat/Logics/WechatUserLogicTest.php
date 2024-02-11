<?php

namespace app\Modules\tao\A0\wechat\Logics;

use app\Modules\tao\A0\wechat\Logics\WechatUserLogic;
use app\Modules\tao\A0\wechat\Models\WechatUserUnionid;
use PHPUnit\Framework\TestCase;

class WechatUserLogicTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    const userInfo = [
        'app_id' => '123456',
        'openid' => 'op-abc123',
        'headimgurl' => 'https://test.com/a.png',
        'unionid' => 'un-def456',
        'nickname' => 'test123',
        'sex' => 0,
        'language' => '',
        'city' => '', 'country' => '',
        'subscribe_time' => 1700189523,
    ];

    public function testOfficialUser()
    {

        $app = \Mockery::mock(\EasyWeChat\OfficialAccount\Application::class);
        $app->shouldReceive('getConfig')->andReturn(new class {
            public function get($key)
            {
                return WechatUserLogicTest::userInfo[$key];
            }
        });
        $app->shouldReceive('getClient')->andReturn(new class {
            public function get($url, $param)
            {
                return new class {
                    public function toArray()
                    {

                        return WechatUserLogicTest::userInfo;
                    }
                };
            }
        });

        $data = WechatUserLogic::officialUser($app, self::userInfo['openid']);
        $this->assertTrue($data->user_id > 0);

        $this->assertTrue(WechatUserUnionid::queryBuilder()
            ->where([
                'user_id'=>$data->user_id,'unionid'=>self::userInfo['unionid']
            ])->exits());



    }
}