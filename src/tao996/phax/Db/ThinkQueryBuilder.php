<?php

namespace Phax\Db;


use Phax\Utils\MyHelper;
use Phax\Utils\MyData;

/**
 * 包装查询条件，以符合 TP 写法
 * @link https://docs.phalcon.io/5.0/en/db-phql#query-builder
 */
class ThinkQueryBuilder
{
    /**
     * @var \Phalcon\Mvc\Model\Query\Builder
     */
    protected $builder;
    /**
     * @var \Phax\Mvc\Model
     */
    protected $model;

    const operations = [
        '=', '>', '>=', '<', '<=', '<>', '!='
    ];

    public static function withParams(array $params = [
        'container' => null,
    ])
    {

    }

    /**
     * @param string $class
     */
    protected function __construct(mixed $class)
    {
        $this->builder = new \Phalcon\Mvc\Model\Query\Builder();
        if ($class instanceof \Phax\Mvc\Model) {
            $this->model = $class;
        } else {
            $this->model = call_user_func([$class, 'getObject']);
        }
        $this->builder->from(get_class($this->model));
        return $this;
    }

    /**
     * @param mixed $class 模型类必须继续 \Phax\Mvc\Model
     * @return ThinkQueryBuilder
     */
    public static function with(mixed $class)
    {
        return new ThinkQueryBuilder($class);
    }

    /**
     * PHQL 查询
     * @link https://docs.phalcon.io/5.0/en/db-phql#query-builder
     * 搜索条件 <pre>
     * ->columns("inv_id, inv_title")
     * ->columns(["inv_cst_id","inv_total" => "SUM(inv_total)",])
     * ->distinct("status")
     * ->from(Invoices::class) 或者 ->from([Invoices::class,Customers::class])
     * ->from(['i' => Invoices::class,'c' => Customers::class,])
     * ->join(Customers::class) // join/leftJoin/rightJoin/innerJoin
     * ->join(Customers::class,"Invoices.inv_cst_id = Customers.cst_id")
     * ->join(Customers::class,"Invoices.inv_cst_id = c.cst_id","c")
     * ---- 条件：允许在最后 execute 再绑定参数
     * ->where("SUM(Invoices.inv_total) > :sum: AND inv_cst_id > :cst_id:")
     * ->where("SUM(Invoices.inv_total) > :sum: AND inv_cst_id > :cst_id:",["sum"=> PDO::PARAM_INT,"cst_id" => PDO::PARAM_INT,])
     * ->where("SUM(Invoices.inv_total) > :sum:",["sum" => 1000,],["sum" => PDO::PARAM_INT,])
     * ->setBindTypes(["sum" => PDO::PARAM_INT,])->setBindTypes(["cst_id" => PDO::PARAM_INT,],true);
     * ->setBindParams(["sum" => 1000,])->setBindParams(["cst_id" => 10,],true) // $bindParams, $merge=false 合并
     * ->andWhere("SUM(Invoices.inv_total) > 1000")
     * ->andWhere("SUM(Invoices.inv_total) > :sum:", [ "sum" => 1000, ], [ "sum" => PDO::PARAM_INT, ] )
     * ->inWhere("Invoices.inv_id",[1, 3, 5])
     * ->notInWhere("Invoices.inv_id",[1, 3, 5]);
     * ->orWhere("SUM(Invoices.inv_total) > 1000")
     * ->orWhere("SUM(Invoices.inv_total) > :sum:",["sum" => 1000,],["sum" => PDO::PARAM_INT,])
     * ->betweenWhere("Invoices.inv_total", 1000, 5000 ) // $expr, $min, $max
     * ->notBetweenWhere("Invoices.inv_total",1000,5000);
     * 分组 =========================
     * ->groupBy(["Invoices.inv_cst_id",])
     * 过滤 =========================
     * ->having("SUM(Invoices.inv_total) > 1000")
     * ->having("SUM(Invoices.inv_total) > :sum:",["sum" => 1000,],["sum" => PDO::PARAM_INT,])
     * ->orHaving("SUM(Invoices.inv_total) > 1000")
     * ->orHaving("SUM(Invoices.inv_total) > :sum:",["sum" => 1000,],["sum" => PDO::PARAM_INT,])
     * ->inHaving("SUM(Invoices.inv_total)",[1000,5000,]) // $expr, array $value, $operator=OPERATOR_AND默认|OPERATOR_OR
     * ->notInHaving("SUM(Invoices.inv_total)",[1000,5000,]);
     * ->andHaving("SUM(Invoices.inv_total) > 1000")
     * ->andHaving("SUM(Invoices.inv_total) > :sum:", [ "sum" => 1000, ], [ "sum" => PDO::PARAM_INT, ] )
     * ->betweenHaving("SUM(Invoices.inv_total)", 1000, 5000 ) // $expr, $min, $max
     * ->notBetweenHaving("SUM(Invoices.inv_total)",1000,5000)
     * 其它 ============================
     * ->orderBy("Invoices.inv_total") 或者 ->orderBy(["Invoices.inv_total DESC",])
     * ->limit(100)->offset(30) 或者 ->limit(100, 30) // $limit, $offset
     * ->getQuery()
     * ->execute() 或者 ->execute(['cst_id' => 1,'total'  => 1000,])
     * </pre>
     */
    public function builder(): \Phalcon\Mvc\Model\Query\Builder
    {
        return $this->builder;
    }


    /**
     * 设置条件，支持以下写法 <pre>
     * ->where(5) // id 查询
     * ->where('name = "phx"')
     * ->where('name', 'phx')
     * ->where('name', '=', 'phx')
     * </pre>
     * @return $this
     */
    public function where($name, $opt = null, $value = null)
    {
        if (is_callable($name)) { // 回调函数
            $name($this->builder);
            return $this;
        }
        if (is_int($name)) { // 主键
            $this->builder->andWhere($this->model->getPrimaryKey() . '=' . $name);
            return $this;
        }
        if (is_null($opt) && is_null($value)) { // 直接的字符串
            $this->builder->andWhere($name);
            return $this;
        }
        if (!in_array($opt, self::operations)) { // 等值
            $value = $opt;
            $opt = '=';
        }
//        dd($this->getRawSQL(),$this->model->getDataTypeBinds($name));
        $this->builder->andWhere(
            join(' ', [$name, $opt, ':' . $name . ':']),
            [$name => $value],
            [$name => $this->model->getDataTypeBinds($name)]
        );
        return $this;
    }

    /**
     * @param string $name
     * @param array $values
     * @return $this
     */
    public function inWhere(string $name, array $values)
    {
        $this->builder->inWhere($name, $values);
        return $this;
    }

    /**
     * 子查询
     * @param string $name
     * @param string $sql 原生 SQL 语句
     * @return $this
     * @deprecated 有 bug，会自动在 sql 两边添加上单引号
     */
    public function inSubquery(string $name, string $sql)
    {
        $this->builder->andWhere($name . ' IN ( :' . $name . ': )', [
            $name => $sql
        ], [$name => \Phalcon\Db\Column::BIND_SKIP]); // 没作用，还是有 '' 号
        return $this;
    }

    public function getRawSQL()
    {
// https://stackoverflow.com/questions/21339274/get-raw-sql-from-phalcon-query-builder
        // obias Sette
        $data = $this->builder->getQuery()->getSql();

        ['sql' => $sql, 'bind' => $binds, 'bindTypes' => $bindTypes] = $data;

        $finalSql = $sql;
        foreach ($binds as $name => $value) {
            $formattedValue = $value;

            if (\is_object($value)) {
                $formattedValue = (string)$value;
            }

            if (\is_string($formattedValue)) {
                $formattedValue = sprintf("'%s'", $formattedValue);
            }
            $finalSql = str_replace(":$name", $formattedValue, $finalSql);
        }
        return $finalSql;
    }

    public function softDelete()
    {
        $this->builder->andWhere($this->model->getDeleteTimeName() . ' IS NULL');
        return $this;
    }

    /**
     * 排序
     * @param string|array $order 'id desc', ['id=>'desc'], ['id desc']
     * @return self
     */
    public function order($order)
    {
        if (is_array($order)) {
            $orders = [];
            foreach ($order as $key => $value) {
                if (is_int($key)) {
                    $orders[] = $value;
                } else {
                    $orders[] = $key . ' ' . $value;
                }
            }
            $this->builder->orderBy(join(',', $orders));
        } else {

            $this->builder->orderBy($order);
        }
        return $this;
    }

    public function limit($limit, $offset = 0)
    {
        $this->builder->limit($limit, $offset);
    }

    public function offset($offset)
    {
        $this->builder->offset($offset);
    }

    /**
     * 设置查询的列
     * @param string|array $fields
     * @return self
     */
    public function select($fields)
    {
        $this->builder->columns($fields);
        return $this;
    }

    /**
     * 查询集合
     * @param string|array|null $field
     * @return array
     */
    public function find(string|array $field = null): array
    {
        if (!is_null($field)) {
            $this->builder->columns($field);
        }
        return $this->builder->getQuery()->execute()?->toArray() ?: [];
    }

    /**
     * 获取第1条记录指定列的值
     * @param $column
     * @return mixed
     */
    public function value($column)
    {
        $this->builder->columns($column);
        if ($row = $this->builder->getQuery()->execute()->getFirst()?->toArray()) {
            return $row[$column];
        };
        return null;
    }

    /**
     * 查询某个列值，并将其查询结果合并成一维数组
     * @param string $field 列值
     * @return array
     */
    public function values($field): array
    {
        $rows = $this->column($field);
        return MyHelper::pluck($rows, $field);
    }

    /**
     *
     * 获取集合列值
     * @param string $fields
     * @param string|null $key
     * @return array
     */
    public function column($fields, $key = null): array
    {
        $columns = is_array($fields) ? $fields : explode(',', $fields);
        if (!empty($key) && !in_array($key, $columns)) {
            array_push($columns, $key);
        }
        $this->builder->columns($columns);
        $rows = $this->builder->getQuery()->execute()?->toArray() ?: [];
        return MyData::resetRowsKey($rows, $key);
    }


    /**
     * 查询单条记录
     * @return null|\Phalcon\Mvc\Model\Row
     */
    public function findFirst()
    {
        return $this->builder->getQuery()->execute()->getFirst();
    }
}