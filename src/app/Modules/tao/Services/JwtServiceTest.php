<?php

namespace App\Modules\tao\Services;

use PHPUnit\Framework\TestCase;

class JwtServiceTest extends TestCase
{
    public function testCreateJwt()
    {
        $jwt = new JwtService();
        $jwt->setAudience('miniapp');
        $token = $jwt->getToken(['name' => 'phax']);
        $this->assertNotEmpty($token);
    }

    public function testJwt()
    {
        $jwt = new JwtService();
        $jwt->setAudience('miniapp');
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImN0eSI6ImFwcGxpY2F0aW9uL2pzb24ifQ.eyJleHAiOjE2OTk3OTM1MjQsImlzcyI6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA3MSIsImlhdCI6MTY5OTYyMDcyNCwibmJmIjoxNjk5NjIwNjY0LCJzdWIiOiJqd3QiLCJhdWQiOlsibWluaWFwcCJdLCJuYW1lIjoicGhheCJ9.ZK_fJHkq3opo2wP4chVC_HASyvVcjym-RccbJgLDuvM";
        /*
        Array (7) (
          [exp] => Integer (1699793524)
          [iss] => String (21) "http://localhost:8071"
          [iat] => Integer (1699620724)
          [nbf] => Integer (1699620664)
          [sub] => String (3) "jwt"
          [aud] => Array (1) (
            [0] => String (7) "miniapp"
          )
          [name] => String (4) "phax"
        )
         */
        $payload = $jwt->parser($token);
        $this->assertEquals('phax', $payload['name']);
    }
}