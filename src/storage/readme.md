```
storage
    |- app #保存运行时的文件，如 `supervisord.pid`, `workerman.pid`
    |- cache
        |-- session     # 文件类型的 session
        |-- view        # volt 编译后的模板
    |- data             # 程序运行期间重要的数据（）
        |-- migration   # 执行 php artisan migration g 命令后导出的数据库文件
    |- logs                 # 日志
        |-- app-xxx.log     # 站点运行期间错误信息
        |-- php_errors.log  # 命令行错误信息
        |-- sql-xxx.log     # 站点 SQL 语句
        |-- workerman.log
        |-- xdebug.log
```