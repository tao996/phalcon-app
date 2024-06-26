<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */

//declare(ticks=1);

namespace App\Cli;

use GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 * @link [文档](https://www.workerman.net/doc/gateway-worker/event-functions.html)
 */
class WebsocketEvents
{
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param string $client_id 连接id
     * @throws \Exception
     */
    public static function onConnect(string $client_id): void
    {
        echo 'onConnect:', $client_id, PHP_EOL;
        // 向当前client_id发送数据
        Gateway::sendToClient($client_id, "Hello $client_id\r\n");
        // 向所有人发送
        Gateway::sendToAll("$client_id login\r\n");
    }

    /**
     * 当客户端发来消息时触发
     * @param string $client_id 连接id
     * @param mixed $message 具体消息
     * @throws \Exception
     */
    public static function onMessage(string $client_id, mixed $message): void
    {
        if ($message == 'reload') {
            system('php /var/www/app/Cli/index.php reload');
            Gateway::sendToClient($client_id, "reload success\r\n");
            return;
        }
        if (json_validate($message)) {
            $message = json_decode($message, true);
        }
        // 向所有人发送
        echo 'From:', $client_id, ';Message:', $message, PHP_EOL;
        Gateway::sendToAll("$client_id said $message\r\n");
    }

    /**
     * 当用户断开连接时触发
     * @param string $client_id 连接id
     * @throws \Exception
     */
    public static function onClose(string $client_id): void
    {
        // 向所有人发送
        echo 'onClose:', $client_id, PHP_EOL;
        GateWay::sendToAll("$client_id logout\r\n");
    }
}
