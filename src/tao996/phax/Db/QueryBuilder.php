<?php

namespace Phax\Db;

use Phalcon\Mvc\Model;
use Phax\Foundation\Application;
use Phax\Support\Validate;
use Phax\Utils\MyData;

/**
 * 请求参数处理 Phalcon 写法
 * @link https://docs.phalcon.io/5.0/en/db-phql#parameters-1
 */
class QueryBuilder
{
    private array $_parameter = [
//        'container' => null,
        'bind' => [],
        'bindTypes' => [],
//        'columns' => [],
        'conditions' => [],
//        'distinct' => '', // distinct column
//        'group' => [],
//        'having' => '', // having columns
//        'joins' => [],
//        'limit' => [],
//        'offset' => 15,
//        'models' => [],
//        'order' => [],
    ];
    private \Phax\Mvc\Model $model;

    public function __construct(\Phax\Mvc\Model $model = null)
    {
        if (!is_null($model)) {
            $this->model = $model;
            $this->_parameter['models'] = get_class($model);
        }
    }


    public static function with(string|\Phax\Mvc\Model|null $model, bool $softDelete = true): QueryBuilder
    {
        if (empty($model)) {
            throw new \Exception('model is empty in QueryBuilder.with');
        }
        if (is_string($model)) {
            $model = call_user_func([$model, 'getObject']);
        }
        $qb = new QueryBuilder($model);
        if ($softDelete) {
            $qb->softDelete();
        }
        return $qb;
    }

    public function container($di): static
    {
        $this->_parameter['container'] = $di;
        return $this;
    }

    /**
     * @param $columns array|string 查询的字段
     * @return $this
     */
    public function columns(array|string $columns): static
    {
        if (empty($columns)) {
            return $this;
        }
        if (func_num_args() > 1) {
            throw new \Exception('params columns should be "id,age" or ["id","age"] in queryBuilder.columns');
        }
        $this->_parameter['columns'] = is_array($columns) ? join(',', $columns) : $columns;
        return $this;
    }

    /**
     * columns 的同名方法
     * @param $fields array|string 查询的字段
     * @return $this
     * @throws \Exception
     */
    public function field(array|string $fields): static
    {
        return $this->columns($fields);
    }

    /**
     * like 查询
     * @param string $name 字段名
     * @param mixed $v 值，不需要填写 %% 号
     * @return $this
     * @throws \Exception
     */
    public function like(string $name, mixed $v): static
    {
        if (!empty($v)) {
            $this->condition($name . ' LIKE :' . $name . ':', '%' . $v . '%', \PDO::PARAM_STR);
        }
        return $this;
    }

    public function orLike(array $names, mixed $v): static
    {
        if (!empty($v)) {
            $condition = join(' OR ', array_map(function ($name) {
                return $name . ' LIKE :' . $name . ': ';
            }, $names));
            $this->condition($condition, $v, \PDO::PARAM_STR);
        }
        return $this;
    }

    public function range(string $name, mixed $min, mixed $max, int $type): static
    {
        if (!empty($min)) {
            $this->opt($name, '>=', $min, $type);
        }
        if (!empty($max)) {
            $this->opt($name, '<=', $max, $type);
        }
        return $this;
    }

    /**
     * 不查询列值
     * @param array $columns
     * @return $this
     */
    public function excludeColumns(array $columns = []): static
    {
        $row = $this->model->getModelsMetaData()->getAttributes($this->model);
        $this->columns(array_diff($row, $columns));
        return $this;
    }

    /**
     * 一次性设置查询条件
     * @param string $condition 'created > :min: AND created < :max:'
     * @param array $bindValues ['min' => '2013-01-01', 'max' => '2013-10-10']
     * @param array $bindTypes ['min' => \PDO::PARAM_STR, 'max' => \PDO::PARAM_STR]
     * @return $this
     */
    public function conditions(string $condition, array $bindValues = [], array $bindTypes = []): static
    {
        $this->_parameter['conditions'] = $condition;
        $this->_parameter['bind'] = $bindValues;
        $this->_parameter['bindTypes'] = $bindTypes;
        return $this;
    }

    /**
     * 获取条件中的绑定参数
     * @param $condition string 如条件为 age=:xxx:
     * @return array 绑定参数的名称 xxx
     * @throws \Exception
     */
    private function takeBindKey(string $condition): array
    {
        preg_match_all('|:(\w+):|', $condition, $match);
        if (empty($match[1])) {
            throw new \Exception('could not find the bindValue :name: in queryBuilder.takeBindKey');
        }
        return $match[1];
    }

    private string $_conditionSQL = ''; // prefix '1=1';

    /**
     * 条件搜索，需要使用 :: 占位符
     * @param string $condition 条件 age = :min:； 或者直接 age=5
     * @param mixed|null $value 值，如果值为空，则会跳过值绑定
     * @param int $type 绑定类型
     * @throws \Exception
     */
    public function condition(string $condition, mixed $value = null, int $type = \PDO::PARAM_STR): static
    {

        $this->_conditionSQL .= (' AND ' . $condition);
        if (!is_null($value)) {
            foreach ($this->takeBindKey($condition) as $name) {
                $this->_parameter['bind'][$name] = $value;
                $this->_parameter['bindTypes'][$name] = $type;
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function softDelete(): static
    {
        if ($this->model->isSoftDelete()) {
            $this->_conditionSQL .= (' AND ' . $this->model->getDeleteTimeName() . ' IS NULL');
        }
        return $this;
    }

    private int $numberLen = 0;

    /**
     * 设置条件,支持多种格式查询 <pre>
     * 1. 直接 sql 语句，如 where('id=5'), where(['id'=>5, 'age'=>6]), where(['id=5','age=6'])
     * 2. name,value 格式，如 where('id',5), where('id',[1,2,3])
     * 3. name,opt,value 格式，如 where('id','=',5)
     * </pre>
     * @throws \Exception
     */
    public function where(...$params): static
    {
        $paramsLen = count($params);
        switch ($paramsLen) {
            case 1:
                if (is_array($params[0])) {
                    foreach ($params[0] as $key => $value) {
                        if (is_string($key) && is_scalar($value)) { // ['id'=>5]
                            $this->opt($key, '=', $value);
                        } elseif (is_int($key) && is_string($value)) { // [0=>'id=5']
                            $this->and($value, true);
                        } else {
                            throw new \Exception('unsupported conditions in queryBuilder.where');
                        }
                    }
                } else {
                    $this->and($params[0], true);
                }
                break;
            case 2:
                if (is_array($params[1])) {
                    if (is_numeric(end($params[1]))) {
                        $this->inInt($params[0], $params[1]);
                    } else {
                        $this->inString($params[0], $params[1]);
                    }
                    break;
                }
                $this->opt($params[0], '=', $params[1]);
                break;
            case 3:
                $this->opt($params[0], $params[1], $params[2]);
                break;
            default:
                throw new \Exception('unsupported params in queryBuilder.where');
        }
        return $this;
    }

    private array $optNames = [];

    /**
     * 操作
     * @param string $name 字段名称
     * @param string $opt 操作符 like, =, >= ...
     * @param mixed $value 字段值
     * @param int $bindType 绑定类型，默认 -1 表示从模型中获取，其它使用 \PDO::PARAM_XXX
     * @return static
     * @throws \Exception
     */
    public function opt($name, $opt, $value, int $bindType = -1): static
    {
        if (in_array($name, $this->optNames)) {
            return $this;
        }
        $this->optNames[] = $name; // 缓存 $name
        $this->_conditionSQL .= " AND {$name} {$opt} ?{$this->numberLen}";
        switch (strtolower($opt)) {
            case 'like':
                $value = '%' . $value . '%';
                $bindType = \PDO::PARAM_STR;
                break;
        }
        if ($bindType == -1) {
            if (!empty($this->model)) {
                $bindType = $this->model->getDataTypeBinds($name);
            } else {
                if (is_string($value)) { // 需要放在前面，否则手机号之类的就直接被作为 int 处理掉
                    $bindType = \PDO::PARAM_STR;
                } elseif (is_bool($value)) {
                    $bindType = \PDO::PARAM_BOOL;
                } elseif (is_numeric($value)) { // 数字/数字字符串
                    // https://www.php.net/manual/zh/function.is-numeric.php
                    $bindType = \PDO::PARAM_INT;
                } else {
                    $bindType = \PDO::PARAM_STR;
                }
            }
        }
        $this->_parameter['bind'][$this->numberLen] = $value;
        $this->_parameter['bindTypes'][$this->numberLen] = $bindType;
        $this->numberLen++;
        return $this;
    }

    public function string(string $name, $value, $allowEmpty = false): static
    {
        if ($allowEmpty || !empty($value)) {
            $this->opt($name, '=', $value, \PDO::PARAM_STR);
        }
        return $this;
    }

    /**
     * 绑定一个整数
     * @param string $name 字段名称
     * @param mixed $value 待检查的值，会被 intval 处理
     * @param bool $skipEmpty 如果为空值，则跳过
     * @return $this
     */
    public function int(string $name, string|int|null $value, bool $skipEmpty = true): static
    {
        $v = intval($value);
        if (empty($v) && $skipEmpty) {
            return $this;
        }

        $this->opt($name, '=', $v, \PDO::PARAM_INT);
        return $this;
    }

    public function inInt(string $name, array $values): static
    {
        if (!empty($values)) {
            foreach ($values as $value) {
                Validate::mustInt($value);
            }
            $this->_conditionSQL .= ' AND ' . $name . ' IN (' . join(',', $values) . ')';
        }
        return $this;
    }

    /**
     * @param string $name
     * @param array $values
     * @return $this
     * @deprecated 此方法需要你自己确保字符串的安全（仅供内部使用）
     */
    public function inString(string $name, array $values): static
    {
        if (!empty($values)) {
            $this->_conditionSQL .= ' AND ' . $name . ' IN (' . join(',', array_map(function ($v) {
                    return '"' . $v . '"';
                }, $values)) . ')';
        }
        return $this;
    }

    /**
     * 添加一个简单的条件操作
     * @param string $condition 简单条件，示例：id=5
     * @param bool $compare 只有条件为 true 时，才会启用
     * @return $this
     */
    public function and(string $condition, bool $compare): static
    {
        if ($compare) {
            $this->_conditionSQL .= ' AND ' . $condition;
        }
        return $this;
    }

    /**
     * @param string $name
     * @param string|int $value
     * @param bool $allowEmpty
     * @return $this
     */
    public function notEqual(string $name, string|int $value, bool $allowEmpty = false): static
    {
        if (empty($value) && !$allowEmpty) {
            return $this;
        }
        $this->opt($name, '!=', $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        return $this;
    }


    /**
     * 分组
     * @param array $fields ['id', 'name']
     * @return self
     */
    public function group(array $fields): static
    {
        $this->_parameter['group'] = $fields;
        return $this;
    }

    /**
     * 过滤
     * @param string $filter 过滤条件 "status=1"
     * @return $this
     */
    public function having(string $filter): static
    {
        $this->_parameter['having'] = $filter;
        return $this;
    }

    /**
     * 排序，支持多种写法 <pre>
     * 'id' 等价于 'id asc'
     * 'a_id, b_id' 等价于 'a_id asc, b_id asc'
     * </pre>
     * @param string|array $order 排序条件
     * @return self
     */
    public function order(array|string $order): static
    {
        $this->_parameter['order'] = $order;
        return $this;
    }

    /**
     * 分页
     * @param int $page 第几页，首页为0
     * @param int $limit
     * @return self
     */
    public function pagination(int $page, int $limit = 15): static
    {
        $this->_parameter['limit'] = $limit > 0 ? $limit : 15;
        $this->_parameter['offset'] = (max($page, 0)) * $limit;
        return $this;
    }

    /**
     * 取消分页
     * @return $this
     */
    public function disabledPagination(): static
    {
        unset($this->_parameter['limit'], $this->_parameter['offset']);
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->_parameter['limit'] = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->_parameter['offset'] = $offset;
        return $this;
    }

    /**
     * 使用 distinct 时会影响 find 的结果，自动提取列值
     * @param string $name
     * @return $this
     */
    public function distinct(string $name): static
    {
        $this->columns('distinct(' . $name . ') AS ' . $name);
        $this->_parameter['distinct'] = $name;
        return $this;
    }

    public function joins(array $joins): static
    {
        $this->_parameter['joins'] = $joins;
        return $this;
    }

    public function getParameter(): array
    {

        if (!empty($this->_conditionSQL)) {
            if (empty($this->_parameter['conditions'])) {
                $this->_parameter['conditions'] = '1=1' . $this->_conditionSQL;
            } else {
                $this->_parameter['conditions'] .= $this->_conditionSQL;
            }
            $this->_conditionSQL = ''; // 防止多次调用重复拼接
        }
        return $this->_parameter;
    }

    public function builder(): \Phalcon\Mvc\Model\Query\Builder
    {
        if (empty($this->_parameter['container'])) {
            $this->_parameter['container'] = Application::di();
        }
        return new \Phalcon\Mvc\Model\Query\Builder($this->getParameter());
    }

    public function count(): \Phalcon\Mvc\Model\ResultsetInterface|int
    {
        $params = $this->getParameter();
        return $this->model::count($params);
    }

    /**
     * @throws \Exception
     */
    public function exits(): bool
    {
        $this->columns('id');
        return !empty($this->findFirst());
    }

    private array $joinInfo = [];

    /**
     * TODO 联表查询；只会影响到 find/findFirst
     * @param string $referenceModel Profile::class 联表类名
     * @param array|string $fields 查询的字段
     * @param string $foreignKey 外键
     * @param string $referenceModelKey 联表类，默认为 id
     * @return $this
     */
    public function join(string $referenceModel, mixed $fields, string $foreignKey, string $referenceModelKey = 'id'): static
    {
        $parts = explode('\\', $referenceModel);
        $this->joinInfo[] = [
            $referenceModel, $fields, $foreignKey, $referenceModelKey,
            lcfirst(end($parts)),// 4
        ];
//        dd($this->joinInfo);
        return $this;
    }

    /**
     * @param array $rst
     * @param bool $deepArray 是否多维数组
     * @return void
     */
    private function doJoinWithResult(array &$rst, bool $deepArray = false): void
    {
        if ($deepArray) {
            if ($this->joinInfo) {
                foreach ($this->joinInfo as $joinInfo) {
                    $ids = [];
                    foreach ($rst as $item) {
                        $ids[] = $item[$joinInfo[2]];
                    }
                    $rows = QueryBuilder::with($joinInfo[0])->inInt($joinInfo[3], $ids)->findColumn([$joinInfo[3], $joinInfo[1]], $joinInfo[3]);

                    foreach ($rst as $index => $item) {
                        $key = $item[$joinInfo[2]];
                        $rst[$index][$joinInfo[4]] = $rows[$key] ?? [];
                    }
                }
            }
        } else {
            foreach ($this->joinInfo as $joinInfo) {
                if (isset($rst[$joinInfo[2]])) {
                    $rst[$joinInfo[4]] = QueryBuilder::with($joinInfo[0])->int($joinInfo[3], $rst[$joinInfo[2]])
                        ->columns([$joinInfo[3], $joinInfo[1]])
                        ->findFirst();

                }
            }
        }
    }

    /**
     * 查寻符合条件的所有记录
     * @param callable|null $callback 回调函数
     * @return array
     */
    public function find(callable $callback = null): array
    {
//        dd($this->builder()->getQuery()->getSql());
        $rows = $this->builder()->getQuery()->execute()?->toArray();
        if (is_null($rows)) {
            return [];
        }
        if (!empty($this->_parameter['distinct'])) {
            return array_column($rows, $this->_parameter['distinct']);
        }
        $this->doJoinWithResult($rows, true);
        if ($callback && $rows) {
            $callback($rows);
        }

        return $rows;
    }

    /**
     * 查询符合条件的首行记录
     * @param bool $toArray 如果为 false 则不会联表查询
     * @param callable|null $callback 回调函数
     * @return array|Model|null|mixed|\Phalcon\Mvc\Model\Row 注意返回的不是具体模型，可能需要再次转换
     */
    public function findFirst(bool $toArray = true, callable $callback = null)
    {
        $this->_parameter['limit'] = 1;
        $this->_parameter['offset'] = 0;
        $record = $this->builder()->getQuery()->execute()?->getFirst();
        if (is_null($record)) {
            return $toArray ? [] : null;
        }
        $row = $toArray ? $record->toArray() : $record;
        if ($toArray) {
            $this->doJoinWithResult($row, false);
        }
        if ($callback && $row) {
            $callback($row);
        }
        return $row;
    }

    public function findFirstAssign(): bool
    {
        if ($data = $this->findFirst()) {
            $this->model->assign($data);
            return true;
        }
        return false;
    }

    /**
     * 查询符合条件的所有记录
     * @param array|string $fields 指定要查询的字段
     * @param string|null $key 如果设置，则会将此字段的值提升为查询记录的 key
     * @throws \Exception
     */
    public function findColumn(array|string $fields, string $key = null): array
    {
        $this->columns($fields);
        $rows = $this->find();
        return $key ? MyData::resetRowsKey($rows, $key) : $rows;
    }

    /**
     * 获取第1条记录指定列的值
     * @param string $column
     * @return mixed
     */
    public function value(string $column): mixed
    {
        if ($row = $this->columns($column)->findFirst()) {
            return $row[$column];
        }
        return null;
    }

    /**
     * 删除符合条件的记录 (注意：使用软删除会触发 beforeSave)
     * @return bool
     */
    public function delete(): bool
    {
        return $this->model::find($this->getParameter())->delete();
    }
}