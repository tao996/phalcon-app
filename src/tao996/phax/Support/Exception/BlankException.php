<?php

namespace Phax\Support\Exception;

/**
 * 此异常用于代替代码中的 exit/die
 * 将会被 Phax\Mvc\Response::exception 捕获
 */
class BlankException extends \Exception
{
    public function __construct(string $message = "")
    {
        parent::__construct($message, 0, null);
    }
}