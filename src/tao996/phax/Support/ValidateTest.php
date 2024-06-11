<?php

namespace Phax\Support;

use PHPUnit\Framework\TestCase;

class ValidateTest extends TestCase
{
    public function testRules()
    {
        $data = Validate::rules(['name|用户名' => 'require|min:20|max:20|or:1,2']);
        $this->assertEquals([
            0 => [
                'name' => 'name',
                'title' => '用户名',
                'rules' => [
                    ['require', []],
                    ['min', [20]],
                    ['max', [20]],
                    ['or', [1, 2]]
                ]
            ]
        ], $data);

        $data = Validate::rules(['name' => 'require']);

        $this->assertEquals([
            0 => [
                'name' => 'name',
                'title' => '',
                'rules' => [
                    ['require', []]
                ]
            ]
        ], $data);

    }

    public function testGetCallerValidation()
    {
        $rst = Validate::getCallerValidation('accept', []);
        $this->assertEquals(2, count($rst));

        $rst = Validate::getCallerValidation('notBetween', [
            5, 20
        ]);
        $this->assertEquals(3, count($rst));
        $this->assertEquals(['min' => 5, 'max' => 20], $rst[2]);

        $rst = Validate::getCallerValidation('neq', [
            'name'
        ]);
        $this->assertEquals(3, count($rst));
        $this->assertTrue(!isset($rst[2]));
        $this->assertEquals('name', $rst['with']);
    }

    public function testValidationMessage()
    {
        $v = new \Phalcon\Filter\Validation();
        $v->add('num', new Validation\NotBetweenValidation(
            ['min' => 100, 'max' => 200, 'message' => ':field 的范围值不能在 :min 到 :max 之间']
        ));
        $messages = $v->validate(['num' => 150]);
        $msg = Validate::getMessages($messages);
        $this->assertEquals('num 的范围值不能在 100 到 200 之间', $msg[0]);


//        https://docs.phalcon.io/5.0/en/filter-validation#between
        $v = new \Phalcon\Filter\Validation();
        $v->add(
            "price",
            new \Phalcon\Filter\Validation\Validator\Between(
                [
                    "minimum" => 0,
                    "maximum" => 100,
                    "message" => "The :field must be between :min and :max",
                ]
            )
        );
        $messages = $v->validate(['price'=>150]);
        $msg = Validate::getMessages($messages);
        $this->assertEquals('The price must be between 0 and 100',$msg[0]);
    }

    /**
     * php artisan test --filter testSelfMessage tao996/phalcon/src/Phax/Support/ValidateTest.php
     * @return void
     * @throws \Exception
     */
    public function testSelfMessage()
    {
        $rst = Validate::getCheckMessages([
            'name' => '',
        ], [
            'name' => 'require',
        ], [
            'name.require' => '必须填写用户名'
        ]);
        $this->assertIsArray($rst, '必须返回错误信息');
        $this->assertStringContainsString('必须填写用户名', $rst[0]);

        $rst = Validate::getCheckMessages(
            ['name' => '',],
            ['name|用户名' => 'require'],
        );
        $this->assertIsArray($rst, '必须返回错误信息');
        $this->assertStringContainsString('用户名', $rst[0]);
    }

    public function testAction()
    {

        $rst = Validate::getCheckMessages([
            'created_at' => '2023-06-15 14:00',
            'name' => 'jack',
            'age' => 15,
            'password' => '123456',
            'repassword' => '123456',
            'birthday' => '2023-06-15',
            'width' => 77,
            'price' => -15.6,
            'status' => 'a',
            'type' => 1,
        ], [
            'created_at' => 'date:Y-m-d H:i',
            'name' => 'require',
            'age' => 'between:1,15',
            'password' => 'confirm:repassword',
            'birthday' => 'date',
            'width' => 'digit',
            'price' => 'price',
            'status' => 'in:a,b,c',
            'type' => 'notin:4,5,6',
        ]);
        $this->assertNull($rst, is_array($rst) ? join(',', $rst) : 'success');

        $rst = Validate::getCheckMessages([
            'name' => ''
        ], [
            'name|用户名' => 'require'
        ]);
        $this->assertStringContainsString('用户名', $rst[0]);
    }

    public function testIsPhone()
    {
        $phone = 'abc';
        try {
            Validate::mustPhone($phone);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertStringContainsString('a valid', $e->getMessage());
        }

        Validate::mustPhone('13420000005');
    }

    public function testIsEmail()
    {
        try {
            Validate::mustEmail('5135@qq');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertStringContainsString('a valid', $e->getMessage());
        }

        $this->assertTrue(Validate::isEmail('525@qq.com'));
        $this->assertFalse(Validate::isEmail('5135@qq'));
    }
}