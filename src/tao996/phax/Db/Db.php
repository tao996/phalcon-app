<?php

namespace Phax\Db;

use Phax\Foundation\Application;

class Db
{
    /**
     * 事务执行 （Phalcon Model Db 会执行触发事件）
     * @link https://docs.phalcon.io/3.4/en/db-models-transactions 模型事务
     * @param callable (\Phalcon\Db\Adapter\Pdo\AbstractPdo):void $handle 处理函数，接收参数
     * @param string $name id 连接名称，默认为 db
     * @return void
     * @throws \Exception
     */
    public static function transaction(callable $handle, string $name = 'db'): void
    {
        $PDOConnection = Application::di()->get($name);
        $PDOConnection->begin();
        try {
            $handle($PDOConnection);
            $PDOConnection->commit();
        } catch (\Exception $e) {
            $PDOConnection->rollback();
            throw $e;
        }
    }

    public static function pdoTransaction(callable $handle): void
    {
        $db = pdo();
        $db->beginTransaction();
        try {
// https://www.php.net/manual/zh/pdo.transactions.php#110483
// MySQL, Oracle 的 DDL 语句会自动触发事务
            $handle($db);
            if ($db->inTransaction()) {
                $db->commit();
            }
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * 打印 PDO 语句
     * @link https://stackoverflow.com/questions/210564/getting-raw-sql-query-string-from-pdo-prepared-statements
     * @param string $query
     * @param array $params
     * @return string
     */
    public static function getRawPdoSql(string $query, array $params = []): string
    {
        $keys = array();

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }
        }

        return preg_replace($keys, $params, $query, 1, $count);
    }
}