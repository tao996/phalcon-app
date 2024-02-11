<?php

namespace Phax\Db;

use Phax\Utils\Data;
use PHPUnit\Framework\TestCase;


class QueryBuilderTest extends TestCase
{
    public function testWhere()
    {
        $qb = new QueryBuilder();
        $qb->where(['phone' => '123456789', 'phone_valid' => 1]);
        $rst = $qb->getParameter();
        $this->assertEquals([
            'bind' => ['123456789', 1],
            'bindTypes' => [2, 1]
        ], Data::getByKeys($rst, ['bind', 'bindTypes']));
    }
}