<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * G Theme Internationalization (i18n) Class
 * 
 * Supports: zh-CN (Simplified Chinese), zh-TW (Traditional Chinese), en (English)
 */
class GI18n
{
    /**
     * Current language code
     * @var string
     */
    private static $lang = 'zh-CN';

    /**
     * Loaded translation strings
     * @var array
     */
    private static $strings = [];

    /**
     * Supported languages
     * @var array
     */
    public static $supportedLangs = [
        'zh-CN' => '简体中文',
        'zh-TW' => '繁體中文',
        'en'    => 'English',
    ];

    /**
     * Initialize i18n - detect and load language
     */
    public static function init()
    {
        self::$lang = self::detectLanguage();
        self::loadLanguage(self::$lang);
    }

    /**
     * Detect language from theme settings, then browser
     * @return string
     */
    private static function detectLanguage()
    {
        // 1. Check theme setting
        $options = Helper::options();
        if (!empty($options->lang) && isset(self::$supportedLangs[$options->lang])) {
            return $options->lang;
        }

        // 2. Check browser Accept-Language
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLang = self::parseBrowserLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            if ($browserLang) {
                return $browserLang;
            }
        }

        // 3. Default to zh-CN
        return 'zh-CN';
    }

    /**
     * Parse browser Accept-Language header
     * @param string $header
     * @return string|null
     */
    private static function parseBrowserLanguage($header)
    {
        $langs = explode(',', $header);
        foreach ($langs as $lang) {
            $lang = trim(explode(';', $lang)[0]);
            // Exact match
            if (isset(self::$supportedLangs[$lang])) {
                return $lang;
            }
            // Prefix match (e.g., "zh" matches "zh-CN")
            $prefix = strtolower(explode('-', $lang)[0]);
            if ($prefix === 'zh') {
                // Distinguish Traditional vs Simplified
                $lower = strtolower($lang);
                if (strpos($lower, 'tw') !== false || strpos($lower, 'hk') !== false || strpos($lower, 'hant') !== false) {
                    return 'zh-TW';
                }
                return 'zh-CN';
            }
            if ($prefix === 'en') {
                return 'en';
            }
        }
        return null;
    }

    /**
     * Load language file
     * @param string $lang
     */
    private static function loadLanguage($lang)
    {
        $file = dirname(__DIR__) . '/lang/' . $lang . '.php';
        if (file_exists($file)) {
            self::$strings = include $file;
        } else {
            // Fallback to zh-CN
            $fallback = dirname(__DIR__) . '/lang/zh-CN.php';
            if (file_exists($fallback)) {
                self::$strings = include $fallback;
            }
        }
    }

    /**
     * Get translated string
     * @param string $key Translation key
     * @param mixed ...$args Optional sprintf arguments
     * @return string
     */
    public static function t($key, ...$args)
    {
        $str = isset(self::$strings[$key]) ? self::$strings[$key] : $key;
        if (!empty($args)) {
            return vsprintf($str, $args);
        }
        return $str;
    }

    /**
     * Echo translated string
     * @param string $key Translation key
     * @param mixed ...$args Optional sprintf arguments
     */
    public static function e($key, ...$args)
    {
        echo self::t($key, ...$args);
    }

    /**
     * Get current language code
     * @return string
     */
    public static function getLang()
    {
        return self::$lang;
    }

    /**
     * Get HTML lang attribute value
     * @return string
     */
    public static function getHtmlLang()
    {
        $map = [
            'zh-CN' => 'zh-Hans',
            'zh-TW' => 'zh-Hant',
            'en'    => 'en',
        ];
        return isset($map[self::$lang]) ? $map[self::$lang] : 'zh-Hans';
    }
}
