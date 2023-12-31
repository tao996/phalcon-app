<?php

namespace Phax\Events;

class Profiler
{

    public static function attach()
    {
        $em = eventsManager();
        $profiler = profiler();
        $em->attach('db', function ($event, $connection) use ($profiler) {
            //一条语句查询之前事件，profiler开始记录sql语句
            if ($event->getType() == 'beforeQuery') {
                $profiler->startProfile($connection->getSQLStatement());
            }

            //一条语句查询结束，结束本次记录，记录结果会保存在profiler对象中
            if ($event->getType() == 'afterQuery') {
                $profiler->stopProfile();
            }
        });
        db()->setEventsManager($em);
    }

    public static function outProfiler()
    {
        foreach (profiler()->getProfiles() as $profile) {
            echo "SQL语句: ", $profile->getSQLStatement(), "\n";
            echo "开始时间: ", $profile->getInitialTime(), "\n";
            echo "结束时间: ", $profile->getFinalTime(), "\n";
            echo "消耗时间: ", $profile->getTotalElapsedSeconds(), "\n";
        }
        echo profiler()->getLastProfile()->getSQLStatement(), "\n";
    }
}