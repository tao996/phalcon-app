the language will be load in `Foundation/Application`

* [文档](https://docs.phalcon.io/latest/translate/#native-array)

```php
// 加载语言
Transaction::getInstance()
    ->addDictionary(PATH_PHAX . 'messages/:lang.php')
    ->setLanguage($config->path('app.locale', 'cn'))
    ->loadLast();
```