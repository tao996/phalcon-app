<?php

namespace Phax\I18n;

use Phax\Traits\Singleton;

/**
 * phalcon 默认的翻译不支持二级数组
 * @link https://docs.phalcon.io/5.0/en/translate
 */
class Transaction
{

    /**
     * 默认语言
     * @var string
     */
    public static string $lastLanguage = 'cn';
    public static string $firstLanguage = '';
    public static array $messages = [];
    public static array $dictionary = [];
    private static bool $unLoad = true;

    use Singleton;

    protected function __construct()
    {
    }


    /**
     * @param string $path 词典格式，如 /app/:lang/some.php
     * @return self
     */
    public function addDictionary(string $path): self
    {
        if (!in_array($path, self::$dictionary)) {
            self::$dictionary[] = $path;
        }
        return $this;
    }

    /**
     * 添加语言,用于替换掉 $pathOfDictionary 中的 :lang
     * @param string $language
     * @return self
     */
    public function setLanguage(string $language): self
    {
        if (!empty($language) && !in_array($language, [self::$firstLanguage, self::$lastLanguage])) {
            self::$firstLanguage = $language;
        }
        return $this;
    }

    /**
     * 加载翻译
     * @param string $path
     * @return self
     */
    public function loadDictionary(string $path): static
    {
        if (empty($path)) {
            throw new \Exception('path must not empty when load dictionary');
        }
        self::$unLoad = false;
        foreach ([self::$firstLanguage, self::$lastLanguage] as $lang) {
            if (empty($lang)) {
                continue;
            }
            $translationFile = Lang::interpolate($path, ['lang' => $lang]);

            if (file_exists($translationFile)) {
                self::$messages = array_merge(self::$messages, require_once $translationFile);
                return $this;
            }
        }

        return $this;
    }

    /**
     * 加载最后一本翻译
     * @return $this
     * @throws \Exception
     */
    public function loadLast(): self
    {
        return $this->loadDictionary(end(self::$dictionary));
    }

    /**
     * 加载全部翻译
     * @return $this
     * @throws \Exception
     */
    public function loadAll(): self
    {
        foreach (self::$dictionary as $path) {
            self::loadDictionary($path);
        }
        return $this;
    }

    public static function get($key, array $placeholders = [], string $defMessage = ''): string
    {
        if (self::$unLoad) {
            Transaction::getInstance()->loadAll();
        }
        $message = self::$messages[$key] ?? $defMessage;
        return Lang::interpolate($message, $placeholders);
    }
}