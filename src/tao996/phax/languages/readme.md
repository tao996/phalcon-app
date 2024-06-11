the language will be load in `Foundation/Application`

```php
// 加载语言
Transaction::getInstance()
    ->addDictionary(PATH_PHAX . 'languages/:lang.php')
    ->setLanguage($config->path('app.locale', 'cn'))
    ->loadLast();
```