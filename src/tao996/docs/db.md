# 数据库

## [Database Abstraction Layer](https://docs.phalcon.io/5.0/en/db-layer)

可以理由为 PHP 的 PDO 操作,典型用法

```php
$sql = "UPDATE `co_invoices` 
SET `inv_cst_id`= ?, `inv_title` = ?
WHERE `inv_id` = ?
";
$success = db()->execute( $sql, [1, 'Invoice for ACME Inc.', 4,]);
// 注意问号后面没有数字
```

* Constants 获取数据结果的常量，对应 `\PDO::FETCH_xxx`
* Bind Types 绑定参数类型 `Phalcon\Db\Column::BIND_xxx`
* Column Types 数据表列类型 `Phalcon\Db\Column::TYPE_xxx` 对应数据表中字段创建时可选择的类型

## [Phalcon Query Language](https://docs.phalcon.io/5.0/en/db-phql)

* `Phalcon\Mvc\Model\Query`

PHQL 也是类似原生的 SQL 语句，但底层应该是跟模型类相关的（增删改操作也会触发事件）

```php
$container = Di::getDefault();
$query     = new Query(
    'SELECT * FROM Invoices', $container
);
$invoices = $query->execute();

// 或者
$query = modelsManager()->createQuery(
    'SELECT * FROM Invoices WHERE inv_id = :id:'
);
$invoices = $query->execute([
    'id' => $invoiceId,
]);
// 等价于
$invoices = modelsManager()->executeQuery(
    'SELECT * FROM Invoices WHERE inv_id = :id:',
    [
        'id' => $invoiceId,
    ]
);
// FROM 后面是一个模型，目前会出现 Model 'Xxx' could not be loaded
// 必须写出命名空间 'UPDATE App\Modules\System\Models\SystemConfig SET
// public function executeQuery(string! phql, var placeholders = null, var types = null) -> var
//          public function createQuery(string! phql) -> <QueryInterface>
// return query->execute();
//          public function execute(array bindParams = [], array bindTypes = []) ; phalcon/Mvc/Model/Query.zep
//               public function parse() -> array
//                  Lang::parsePHQL(phql)
//           final protected function executeUpdate(array intermediate, array bindParams, array bindTypes) -> <StatusInterface>
//              会初始化一个 new Static(true)，然后 record->update() =》phalcon/Mvc/Model.zep => $this->save()

$robots = Invoices::findByRawSql(
    'id > ?0',
    [
        10
    ]
);

// 两种写法的比较
modelsManager()->executeQuery('UPDATE '.__CLASS__.' SET value=?0 WHERE gname=?1 AND name=?2',
    [ $value, $gname, $name]);
db()->execute('UPDATE ' . $this->getSource() . ' SET value=? WHERE gname=? AND name=?',
    [ $value, $gname, $name]);
```

* `Phalcon\Mvc\Model\Query\Builder`

提供通过对象的方法，来构建 Query 语法，很不符合习惯

## [Model](https://docs.phalcon.io/5.0/en/db-models)

MVC 中的 M 层，连接业务逻辑和数据库。 与 `PHQL` 相比，Model 具有一些自己的能力。
如果你习惯了 TP/Laravel/Yii 等写法的话，那么 Phalcon 的写法是很不习惯的

```
// Phalcon —— 把条件全部扔进聚合函数里
$average = Invoices::average(['column' => 'inv_total']);
$average = Invoices::average(['inv_cst_id = 1''column' => 'inv_total',]);
$average = Invoices::count('inv_cst_id = 1');

// ThinkPHP
// https://doc.thinkphp.cn/v8_0/model_query.html
User::where('status',1)->avg('score');
User::count();
User::where('status','>',0)->count();
User::max('score');

// Laravel
// https://learnku.com/docs/laravel/10.x/queries/14883#aggregates
Flight::where('active', 1)->count();
Flight::where('active', 1)->max('price');
DB::table('orders')->where('finalized', 1)->avg('price');
DB::table('users')->count()

// Phalcon
$robots = Invoices::findByRawSql('id > ?0',[10]);
```

在实践中，我们主要用来进行 `创建/更新/删除` 操作（触发事件），
较为重要的方法 `assign($data, array $whiteList)` 通常用来赋值，然后进行保存

## 常见错误

* 数据表创建不规范，非软删除字段滥用 NULL 导致添加/更新记录时出现的 `Uncaught TypeError: Cannot assign null to property`
  (如果一个字段允许为 NULL，则模型字段对应的也应该是 NULL)

* 使用了数据库保留字，如 `group` 导致查询错误 `select * from table where group=? and ...`
* 使用 PHQL 时因为命名空间导致的 `Model cound not be load`