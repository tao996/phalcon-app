<?php

namespace Phax\Support;

class Logger
{

    /**
     * 只记录异常主要的栈信息（文件+方法+行数）到日志
     * @param \Exception $e
     * @return void
     */
    public static function exception(\Exception $e): void
    {
        $info = [];
        foreach ($e->getTrace() as $item) {
            if (isset($item['file']) && strrpos($item['file'], 'public/index.php') === false) {
                $info[] = sprintf('%s | %s | %d', $item['file'], $item['function'], $item['line'] ?? -1);
            }
        }

        try {
            logger()->error($e->getMessage() . "\n" . print_r($info, true));
        } catch (\Exception $e) {
            dd('循环异常:', $e->getMessage());
        }
    }

    /**
     * 只记录消息到日志中
     * @param ...$args
     * @return void
     * @throws \Phalcon\Logger\Exception
     */
    public static function info(...$args): void
    {
        if (count($args) === 1) {
            logger()->info(print_r($args[0], true));
        } else {
            logger()->info(print_r($args, true));
        }
    }

    /**
     * 只记录警告级别信息
     * @param ...$args
     * @return void
     * @throws \Phalcon\Logger\Exception
     */
    public static function warning(...$args): void
    {
        if (count($args) === 1) {
            logger()->warning(print_r($args[0], true));
        } else {
            logger()->warning(print_r($args, true));
        }
    }

    /**
     * 只记录错误级别信息
     * @param ...$args
     * @return void
     * @throws \Phalcon\Logger\Exception
     */
    public static function error(...$args): void
    {
        if (count($args) === 1) {
            logger()->error(print_r($args[0], true));
        } else {
            logger()->error(print_r($args, true));
        }
    }

    /**
     * 记录异常详细的栈信息，并重新抛出 $message 异常
     * @param string $message 需要返回给客户端的信息，并重新 throw
     * @param \Exception $e 需要记录的异常
     * @throws \Exception
     */
    public static function Wrap(string $message, \Exception $e)
    {
        logger()->error($e->getMessage());
        logger()->error($e->getTraceAsString());
        throw new \Exception($message);
    }

    /**
     * @param string $message 要返回给客户端显示的信息
     * @param string|array $logMsg 需要记录到日志的信息
     * @param bool $throwMessage 是否再次抛出 $message 异常
     * @throws \Exception
     */
    public static function message(string $message, array|string $logMsg, bool $throwMessage = true): string
    {
        if (is_array($logMsg)) {
            logger()->error($message . PHP_EOL . join(PHP_EOL, $logMsg));
        } else {
            logger()->error($message . PHP_EOL . $logMsg);
        }
        if ($throwMessage) {
            throw new \Exception($message);
        } else {
            return $message;
        }
    }
}