<?php

namespace Phax\Db;

use Phalcon\Db\Index;
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

    /**
     * @param \Phalcon\Db\Adapter\Pdo\AbstractPdo $db
     * @param string $table
     * @param string|null $schema
     * @return array
     * @deprecated 原版调用会产生一个错误
     * TypeError: Phalcon\Db\Index::__construct(): Argument #2 ($columns) must be of type array
     * __construct( $name = '\000', $columns = NULL, $type = 'UNIQUE' ) 这个 $name 不知道是哪里来的
     */
    public static function describeIndexes(\Phalcon\Db\Adapter\Pdo\AbstractPdo $db, string $table, string $schema = null)
    {
        $indexesSQL = $db->getDialect()->describeIndexes($table, $schema);
        $indexes = [];
        foreach ($db->fetchAll($indexesSQL, \PDO::FETCH_ASSOC) as $index) {
            $keyName = $index['key_name'];
            $indexType = $index['index_type'];


            if (!isset($indexes[$keyName])) {
                $indexes[$keyName] = [];
            }

            if (!isset($indexes[$keyName]['columns'])) {
                $columns = [];
            } else {
                $columns = $indexes[$keyName]['columns'];
            }
            $columns[] = $index['column_name'];
            $indexes[$keyName]['columns'] = $columns;

            if ($keyName == "PRIMARY") {
                $indexes[$keyName]["type"] = "PRIMARY";
            } elseif ($indexType == "FULLTEXT") {
                $indexes[$keyName]["type"] = "FULLTEXT";
            } elseif (isset($index['non_unique']) && $index["non_unique"] == 0) {
                $indexes[$keyName]["type"] = "UNIQUE";
            } else {
                $indexes[$keyName]["type"] = "";
            }
        }
        $indexObjects = [];
        foreach ($indexes as $name => $index) {
            $indexObjects[] = new Index($name, $index['columns'], $index['type']);
        }
        return $indexObjects;
    }
}