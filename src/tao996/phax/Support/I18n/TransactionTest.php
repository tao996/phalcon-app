<?php

namespace Phax\Support\I18n;

use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{

    public function testI18n()
    {
        Transaction::getInstance()
            ->addDictionary(__DIR__ . '/:lang.test.php');
        $this->assertEquals('你好', __('text'));

        $this->assertEquals('您好 phalcon', __('say', ['name' => 'phalcon']));

        $text = __('demo', ['field' => '年龄', 'min' => 18, 'max' => 120, 'name' => 'ABC']);
        $this->assertEquals('年龄 的范围为 18 到 120', $text);


        Transaction::getInstance()
            ->addDictionary(__DIR__ . '/:lang.test.php')
            ->setLanguage('en')
            ->loadLast();

        $this->assertEquals('hello', __('text'));
        $this->assertEquals('hello phalcon', __('say', [
            'name' => 'phalcon'
        ]));
    }
}