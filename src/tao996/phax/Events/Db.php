<?php

namespace Phax\Events;

class Db
{
    /**
     * sql 日志
     * @return void
     * @throws \Phalcon\Logger\Exception
     */
    public static function attach()
    {
        $em = eventsManager();

        $path = \config('database.log.path');
        preg_match('|{(\w+)}|',$path, $matches);
        if (!empty($matches)) {
            $path = str_replace($matches[0], date($matches[1]), $path);
        }

        $adapter = new \Phalcon\Logger\Adapter\Stream($path);
        $logger = new \Phalcon\Logger\Logger('messages', [
            'db' => $adapter
        ]);

        $em->attach('db:beforeQuery', function (\Phalcon\Events\Event $event, \Phalcon\Db\Adapter\Pdo\AbstractPdo $db) use ($logger) {
            $logger->info($db->getSQLStatement());
            if (IS_DEBUG) {
                $logger->info(json_encode($db->getSQLVariables()));
            }
//            switch ($event->getType()) {
//                case 'beforeQuery':
//                    $logger->info($db->getSQLStatement());
//                    break;
//
//            }
        });
        db()->setEventsManager($em);
    }
}