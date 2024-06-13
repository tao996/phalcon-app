<?php

namespace Phax\Support\I18n;

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
    public static string $firstLanguage = ''; // 首选语言
    public static array $messages = []; // 保存全部的翻译
    public static array $dictionary = []; // 翻译文件所在的目录
    private static $i18nFiles = []; // 已经加载的翻译文件

    use Singleton;

    protected function __construct()
    {
    }

    /**
     * 指定语言所在目录
     * @param string $path 示例 messages/:lang.php，其中 :lang 会被替换成真正的语言
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
        foreach ([self::$firstLanguage, self::$lastLanguage] as $lang) {
            if (empty($lang)) {
                continue;
            }
            $translationFile = Lang::interpolate($path, ['lang' => $lang]);
            if (file_exists($translationFile) && !in_array($translationFile, self::$i18nFiles)) {
                self::$messages = array_merge(self::$messages, require_once $translationFile);
                self::$i18nFiles[] = $translationFile;
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

    /**
     * 获取翻译后的内容
     * @param string $key
     * @param array $placeholders
     * @param string $defMessage
     * @return string
     * @throws \Exception
     */
    public static function get(string $key, array $placeholders = [], string $defMessage = ''): string
    {
        static $unload = true;
        if ($unload) {
            Transaction::getInstance()->loadAll();
            $unload = false;
        }
        $message = self::$messages[$key] ?? $defMessage;
        if (empty($message)) {
            throw new \Exception('找不到' . $key . '翻译信息的翻译信息，请设置默认提示信息');
        }
        return Lang::interpolate($message, $placeholders);
    }
}