<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
require_once("shortcode.php");

class G
{

    /**
     * 主题版本号
     *
     * @var string
     */
    public static $version = "3.4.1";

    /**
     * 主题配置
     *
     * @var array
     */
    public static $config = [
        'favicon' => '',
        'cdn' => '',
        'background' => '',
        'themeColor' => '',
        'headerColor' => '',
        'secondaryColor' => '',
        'alertColor' => '',
        'hoverColor' => '',
        'enableFrostedGlass' => '',
        'themeRadius' => '',
        'themeShadow' => '',
        'autoBanner' => '',
        'defaultBanner' => '',
        'buildYear' => '',
        'icp' => '',
        'defaultArticlePath' => '',
        'enableIndexPage' => '',
        'advanceSetting' => '',
        'footerLOGO' => '',
        'enableUPYUNLOGO' => '',
        'footerCustom' => '',
        'enableDefaultTOC' => '',
        'autoNightSpan' => '',
        'autoNightMode' => '',
        'commentType' => '',
        'enableLegacy' => '',
        'articleColumns' => '',
        'showArticleBanner' => '',
        'showArticleExcerpt' => '',
    ];

    public static $advanceConfig = [];

    public static $themeUrl = '';

    public static $themeBackup = 'Gbf';

    /**
     * 初始化
     *
     * @return void
     */
    public static function init()
    {
        //读取配置内容
        $options = Helper::options();
        $keys = array_keys(self::$config);
        foreach ($keys as $key)
            if (!empty($options->{$key}))
                self::$config[$key] = $options->{$key};
        if (self::$config['advanceSetting'] != '') {
            $advanceConfig = explode("\n", self::$config['advanceSetting']);
            foreach ($advanceConfig as $item)
                if ($item != '')
                    self::$advanceConfig[explode("=", $item)[0]] = explode("=", $item)[1];
        }
        self::$themeUrl = Helper::options()->themeUrl . '/';
    }

    /**
     * 获取背景信息
     * regex source: https://daringfireball.net/2010/07/improved_regex_for_matching_urls
     *
     * @return string
     */
    public static function getBackground()
    {
        $background = "background";
        $regex = '@(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))@';
        if (self::$config['background'] == '')
            return $background . ": #fff;";
        else if (self::$config['background'] == 'bing')
        {
            $bingP = json_decode(file_get_contents('https://cn.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1'));
            return $background . "-image: url(https://cn.bing.com" . $bingP->{'images'}[0]->{'url'} . ");";
        }
        else if (preg_match($regex, self::$config['background']) == 0)
            return ($background . ": " . self::$config['background'] . ";");
        return $background . "-image: url(" . self::$config['background'] . ");";
    }

    /**
     * 配置主题CSS变量
     *
     * @return string
     */
    public static function setCSSValues()
    {
        $themeColorRaw = self::$config["themeColor"] !== '' ? self::$config["themeColor"] : '#07F';
        $secondaryColorRaw = self::$config["secondaryColor"] !== '' ? self::$config["secondaryColor"] : '#6A6A6A';
        $alertColorRaw = self::$config["alertColor"] !== '' ? self::$config["alertColor"] : '#E74C3C';

        // Required colors: never treated as "auto"
        $themeColor = self::resolveColor($themeColorRaw, $themeColorRaw);
        $secondaryColor = self::resolveColor($secondaryColorRaw, $themeColorRaw);
        $alertColor = self::resolveColor($alertColorRaw, $themeColorRaw);

        // Optional colors: support "auto" (or empty), derived from required colors
        $headerColor = self::resolveColor(self::$config["headerColor"], $secondaryColorRaw, 'header');
        $hoverColor = self::resolveColor(self::$config["hoverColor"], $themeColorRaw, 'hover');

        $glass = self::$config["enableFrostedGlass"] == 1;
        $glassBlur = $glass ? 'blur(12px) saturate(140%)' : 'none';
        $glassBg = $glass ? 'rgba(255, 255, 255, 0.55)' : 'transparent';

        $result = "html {
            --theme-color: " . $themeColor['solid'] . ";
            --theme-color-bg: " . $themeColor['bg'] . ";
            --secondary-color: " . $secondaryColor['solid'] . ";
            --secondary-color-bg: " . $secondaryColor['bg'] . ";
            --alert-color: " . $alertColor['solid'] . ";
            --alert-color-bg: " . $alertColor['bg'] . ";
            --header-color: " . $headerColor['bg'] . ";
            --header-color-solid: " . $headerColor['solid'] . ";
            --hover-color: " . $hoverColor['solid'] . ";
            --hover-color-bg: " . $hoverColor['bg'] . ";
            --theme-glass-blur: " . $glassBlur . ";
            --theme-glass-bg: " . $glassBg . ";
            --theme-radius: " . self::$config["themeRadius"] . ";
            --theme-shadow: " . self::getBoxShadow(self::$config["themeShadow"]) . ";
        ";
        if (isset(self::$advanceConfig['customAnimationInDuration']))
            $result .= "    --theme-animation-in-duration: " . self::$advanceConfig['customAnimationInDuration'] . ";\n        ";
        if (isset(self::$advanceConfig['customAnimationOutDuration']))
            $result .= "    --theme-animation-out-duration: " . self::$advanceConfig['customAnimationOutDuration'] . ";\n    ";
        if (isset(self::$advanceConfig['customHeaderOffsetX']))
            $result .= "    --theme-header-offset-x: " . self::$advanceConfig['customHeaderOffsetX'] . ";\n    ";
        if (isset(self::$advanceConfig['customHeaderOffsetY']))
            $result .= "    --theme-header-offset-y: " . self::$advanceConfig['customHeaderOffsetY'] . ";\n    ";
        $result .= "    }\n";
        if ($glass) {
            // Frosted-glass effect applied wherever --header-color is used as the background.
            // The original CSS rules already paint the background; we layer the blur on top.
            $result .= "
            #header, #widgets .widget, .article-banner, .pap-wrapper, .toolbar, #footer {
                -webkit-backdrop-filter: var(--theme-glass-blur);
                backdrop-filter: var(--theme-glass-blur);
            }
            ";
        }
        // Apply new color variables to common interaction states so they're not dead config.
        // Existing site CSS is untouched; these are additive overrides that respect user values.
        $result .= "
            a:hover, #header-content-right nav a:hover, #footer-nav a:hover,
            a.next:hover, a.prev:hover, #cancel-comment-reply-link:hover,
            .toolbar-btn:hover, .toc-list li:hover {
                color: var(--hover-color);
            }
            .shortcode-warn, .shortcode-notice.alert {
                border-left-color: var(--alert-color) !important;
            }
            .shortcode-download a:hover, #tag-cloud li a:hover {
                border-color: var(--secondary-color);
            }
            #header { background: var(--header-color); }
            ";
        return $result;
    }

    /**
     * 解析颜色配置值
     *
     * 支援：
     *  - 单色（hex、rgb、命名色）
     *  - 多色逗号分隔 -> linear-gradient
     *  - "auto" 或 空 -> 从来源色派生
     *
     * @param string $value 配置值
     * @param string $sourceColor 当 value 为 auto / 空 时用于派生的来源色（已是单色）
     * @param string $mode 派生模式：'hover'|'header'|''（默认与 source 一致）
     * @return array{solid:string, bg:string, isGradient:bool, isAuto:bool}
     */
    public static function resolveColor($value, $sourceColor, $mode = '')
    {
        $value = is_string($value) ? trim($value) : '';
        $isAuto = ($value === '' || strcasecmp($value, 'auto') === 0);

        if ($isAuto) {
            $derived = self::deriveAutoColor(self::extractFirstColor($sourceColor), $mode);
            return [
                'solid' => $derived,
                'bg'    => $derived,
                'isGradient' => false,
                'isAuto' => true,
            ];
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $value)), function ($s) {
            return $s !== '';
        }));

        if (count($parts) >= 2) {
            $gradient = 'linear-gradient(135deg, ' . implode(', ', $parts) . ')';
            return [
                'solid' => $parts[0],
                'bg'    => $gradient,
                'isGradient' => true,
                'isAuto' => false,
            ];
        }

        $solid = count($parts) ? $parts[0] : $value;
        return [
            'solid' => $solid,
            'bg'    => $solid,
            'isGradient' => false,
            'isAuto' => false,
        ];
    }

    /**
     * 从一个颜色字符串中提取第一段颜色（用于派生计算）
     *
     * @param string $value
     * @return string
     */
    public static function extractFirstColor($value)
    {
        if (!is_string($value) || $value === '') return '#07F';
        $parts = array_map('trim', explode(',', $value));
        return $parts[0] !== '' ? $parts[0] : '#07F';
    }

    /**
     * 根据来源色派生 auto 颜色
     *
     * @param string $hex 来源色（hex 字符串，例如 "#07F" 或 "#0077FF"）
     * @param string $mode 'hover' 提亮、 'header' 降饱和加深，其他原样返回
     * @return string
     */
    public static function deriveAutoColor($hex, $mode = '')
    {
        $rgb = self::hexToRgb($hex);
        if ($rgb === null) {
            // 派生失败：使用一组合理的默认值
            switch ($mode) {
                case 'header': return '#6A6A6A';
                case 'hover':  return '#3399FF';
                default:       return $hex;
            }
        }

        list($r, $g, $b) = $rgb;
        list($h, $s, $l) = self::rgbToHsl($r, $g, $b);

        switch ($mode) {
            case 'hover':
                // 悬停色：在主题色基础上轻微提亮
                $l = min(1.0, $l + 0.12);
                $s = min(1.0, $s + 0.05);
                break;
            case 'header':
                // 头部色：降低饱和、整体偏暗，避免太刺眼
                $s = max(0.0, $s - 0.35);
                $l = min(0.55, max(0.25, $l - 0.10));
                break;
            default:
                // 默认轻微调整
                $l = min(1.0, $l + 0.08);
                break;
        }

        list($r2, $g2, $b2) = self::hslToRgb($h, $s, $l);
        return sprintf('#%02X%02X%02X', $r2, $g2, $b2);
    }

    /**
     * Hex -> RGB（支持 #RGB / #RRGGBB，大小写不敏感）
     *
     * @param string $hex
     * @return array{0:int,1:int,2:int}|null
     */
    public static function hexToRgb($hex)
    {
        if (!is_string($hex)) return null;
        $hex = trim($hex);
        if ($hex === '' || $hex[0] !== '#') return null;
        $hex = substr($hex, 1);
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) return null;
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * RGB -> HSL（各分量均为 0-1）
     */
    public static function rgbToHsl($r, $g, $b)
    {
        $r /= 255; $g /= 255; $b /= 255;
        $max = max($r, $g, $b); $min = min($r, $g, $b);
        $h = $s = 0; $l = ($max + $min) / 2;
        if ($max !== $min) {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            if ($max === $r)      $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
            else if ($max === $g) $h = ($b - $r) / $d + 2;
            else                  $h = ($r - $g) / $d + 4;
            $h /= 6;
        }
        return [$h, $s, $l];
    }

    /**
     * HSL -> RGB（返回 0-255 整数）
     */
    public static function hslToRgb($h, $s, $l)
    {
        if ($s == 0) {
            $r = $g = $b = $l;
        } else {
            $hue2rgb = function ($p, $q, $t) {
                if ($t < 0) $t += 1;
                if ($t > 1) $t -= 1;
                if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
                if ($t < 1/2) return $q;
                if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
                return $p;
            };
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $hue2rgb($p, $q, $h + 1/3);
            $g = $hue2rgb($p, $q, $h);
            $b = $hue2rgb($p, $q, $h - 1/3);
        }
        return [
            (int) round($r * 255),
            (int) round($g * 255),
            (int) round($b * 255),
        ];
    }

    /**
     * 根据配置返回阴影值
     *
     * @param int $config
     * @return string
     */
    public static function getBoxShadow($config)
    {
        return ($config == 1) ? "0 6px 12px 0 rgb(31 35 41 / 8%)" : "none";
    }

    /**
     * 替换头图 URL 中的占位符
     *
     * 支持的占位符：
     *   {random}        每次出现都生成一个新的随机字符串（4 位数字）
     *   {random:N}      指定位数的随机数字（N 为 1~12）
     *   {cid}           文章 cid
     *   {slug}          文章 slug
     *   {title}         文章标题（URL 编码后）
     *   {year}/{month}/{day} 文章发布日期
     *   {timestamp}     当前时间戳（毫秒级，确保每次调用都不同）
     *
     * @param String $url
     * @param Object|null $post
     * @return String
     */
    public static function applyBannerPlaceholders($url, $post = null)
    {
        if ($url === null || $url === '')
            return $url;

        // 每次出现的 {random} 都重新生成一次（{random:N} 指定位数）
        $url = preg_replace_callback(
            '/\{random(?::(\d{1,2}))?\}/',
            function ($m) {
                $len = isset($m[1]) && $m[1] !== '' ? max(1, min(12, (int)$m[1])) : 4;
                $min = (int)str_pad('1', $len, '0');
                $max = (int)str_pad('9', $len, '9');
                return (string)mt_rand($min, $max);
            },
            $url
        );

        // 时间戳占位符（毫秒）
        if (strpos($url, '{timestamp}') !== false) {
            $url = str_replace('{timestamp}', (string)round(microtime(true) * 1000), $url);
        }

        if ($post !== null) {
            $replacements = array();
            if (isset($post->cid))     $replacements['{cid}']   = (string)$post->cid;
            if (isset($post->slug))    $replacements['{slug}']  = rawurlencode((string)$post->slug);
            if (isset($post->title))   $replacements['{title}'] = rawurlencode((string)$post->title);
            if (isset($post->created)) {
                $replacements['{year}']  = date('Y', $post->created);
                $replacements['{month}'] = date('m', $post->created);
                $replacements['{day}']   = date('d', $post->created);
            }
            if (!empty($replacements))
                $url = strtr($url, $replacements);
        }

        return $url;
    }

    /**
     * 获取文章头图
     *
     * @param Object $post
     * @return String
     */
    public static function getArticleBanner($post)
    {
        $img = array();
        $banner = $post->fields->imgurl;
        $mirageBanner = $post->fields->thumb;

        if (isset($banner) && $banner != '')
            return self::applyBannerPlaceholders($banner, $post);
        if (isset($mirageBanner) && $mirageBanner != '')
            return self::applyBannerPlaceholders($mirageBanner, $post);
        if (self::$config['defaultBanner'] != '')
            return self::applyBannerPlaceholders(self::$config['defaultBanner'], $post);
        if (self::$config['autoBanner'] == 0)
            return 'none';

        preg_match_all("/<img.*?src=\"(.*?)\".*?\/?>/i", $post->content, $img);
        if (count($img) > 0 && count($img[0]) > 0)
            return $img[1][0];
        else
            return 'none';
    }

    /**
     * 获取文章字段头图
     *
     * @param Object $post
     * @return String
     */
    public static function getArticleFieldsBanner($post)
    {
        $img = array();
        $banner = $post->fields->imgurl;
        $mirageBanner = $post->fields->banner;

        if (isset($banner) && $banner != '')
            return self::applyBannerPlaceholders($banner, $post);
        else if (isset($mirageBanner) && $mirageBanner != '')
            return self::applyBannerPlaceholders($mirageBanner, $post);
        return 'none';
    }

    /**
     * 获取ICP备案号
     *
     * @return String
     */
    public static function getICP()
    {
        if (Helper::options()->icp != '')
            return Helper::options()->icp;
        if (isset(self::$advanceConfig["customICP"]))
            return self::$advanceConfig["customICP"];
        return '还没有备案噢';
    }

    /**
     * 获取赞助按钮文字
     *
     * @return string
     */
    public static function getSponsorText()
    {
        if (isset(self::$advanceConfig["customSponsorText"]))
            return self::$advanceConfig["customSponsorText"];
        return GI18n::t('post.sponsor');
    }

    /**
     * 获取头部文章路径
     *
     * @return String
     */
    public static function getArticlePath()
    {
        $path = Helper::options()->siteUrl;
        if (substr($path, -1) == '/')
            $path = $path . self::$config["defaultArticlePath"];
        else
            $path = $path . '/' . self::$config["defaultArticlePath"];
        return $path;
    }

    /**
     * 以HTML格式返回底部LOGO
     *
     * @return String
     */
    public static function getFooterLogos()
    {
        // Legacy footer assets (UPYUN affiliate badge + footerLOGO images) are
        // only rendered when the "legacy options" master switch is enabled.
        // Without it, the new custom-footer feature is the canonical way to
        // configure footer content.
        if (empty(self::$config['enableLegacy']))
            return '';
        if (self::$config['enableUPYUNLOGO'] == 1)
            $logos = '<a href="https://www.upyun.com/?utm_source=lianmeng&utm_medium=referral"><img alt="upyun" src="' . self::staticUrl('static/img/upyun.png') . '"/></a>';
        else
            $logos = '';
        $imgs = explode(',', self::$config["footerLOGO"]);
        foreach ($imgs as $img)
            if ($img != '')
                $logos = $logos . '<img alt="logo" src="' . $img . '" />';
        return $logos;
    }

    /**
     * 解析单条「自定义底部」项目，支持以下扩展 Markdown 语法：
     *   - 纯文本：           hello world
     *   - 链接：             [text](url)
     *   - 图片：             ![alt](src)
     *   - 图片链接：         [![alt](src)](url)
     *   - 新页面打开扩展：   在 ](url) 之后追加 {newtab} 或 {_blank}
     *   - 原始 HTML：        <a href="url"><img src="..."></a>{newtab?}
     *   - 内置 token：       {upyun}（又拍云联盟 logo + 链接，会被首先展开）
     *   - 佔位空白：         {air} 或 {air:N}（产生 N 个空白佔位元素；
     *                        与 {cell} 配合时自动跨 N 行）
     *   - 网格定位：         在行尾追加 {cell:r,c} 或 {cell:r,c,rs,cs}
     *                        （仅在使用 {grid:...} 容器指令时生效）
     *
     * @param String $line 原始一行
     * @return String 渲染后的 HTML（已转义）
     */
    public static function renderFooterCustomItem($line)
    {
        $line = trim($line);
        if ($line === '')
            return '';

        // 解析尾部的网格定位指令 {cell:r,c[,rs,cs]}，先剥离再渲染内容
        $cellStyle = '';
        $cellHasRowSpan = false;
        if (preg_match('/\s*\{cell:\s*(\d+)\s*,\s*(\d+)(?:\s*,\s*(\d+)\s*,\s*(\d+))?\s*\}\s*$/', $line, $cm)) {
            $row = max(1, (int)$cm[1]);
            $col = max(1, (int)$cm[2]);
            $styles = array(
                'grid-row-start: ' . $row,
                'grid-column-start: ' . $col,
            );
            if (isset($cm[3]) && $cm[3] !== '' && isset($cm[4]) && $cm[4] !== '') {
                $rs = max(1, (int)$cm[3]);
                $cs = max(1, (int)$cm[4]);
                $styles[] = 'grid-row-end: span ' . $rs;
                $styles[] = 'grid-column-end: span ' . $cs;
                $cellHasRowSpan = true;
            }
            $cellStyle = ' style="' . htmlspecialchars(implode('; ', $styles), ENT_QUOTES) . '"';
            $line = trim(preg_replace('/\s*\{cell:[^}]*\}\s*$/', '', $line));
            if ($line === '')
                return '';
        }

        // 当 {air:N} 与 {cell} 结合使用但未手动指定行跨度时，自动跨 N 行
        if ($cellStyle !== '' && !$cellHasRowSpan && preg_match('/^\{air(?::(\d+))?\}$/i', $line, $airMatch)) {
            $airSize = isset($airMatch[1]) && $airMatch[1] !== '' ? max(1, (int)$airMatch[1]) : 1;
            if ($airSize > 1) {
                // 重建 cellStyle，追加 grid-row-end: span N
                $cellStyle = preg_replace(
                    '/style="([^"]*)"/',
                    'style="$1; grid-row-end: span ' . $airSize . '"',
                    $cellStyle
                );
            }
            return '<span class="footer-grid-cell footer-air"' . $cellStyle . '></span>';
        }

        $inner = self::renderFooterCustomInner($line);
        if ($inner === '')
            return '';

        // 当存在网格定位时，使用 <span> 包裹以承载 grid-* 样式
        if ($cellStyle !== '') {
            return '<span class="footer-grid-cell"' . $cellStyle . '>' . $inner . '</span>';
        }
        return $inner;
    }

    /**
     * 实际渲染单项内容（不含网格定位包裹）。
     */
    private static function renderFooterCustomInner($line)
    {
        // 内置 token: {air} — 空白佔位元素
        if (preg_match('/^\{air(?::(\d+))?\}$/i', $line, $am)) {
            $size = isset($am[1]) && $am[1] !== '' ? max(1, (int)$am[1]) : 1;
            if ($size === 1) {
                return '<span class="footer-air"></span>';
            }
            $html = '';
            for ($i = 0; $i < $size; $i++) {
                $html .= '<span class="footer-air"></span>';
            }
            return $html;
        }

        // 内置 token: {upyun}
        if ($line === '{upyun}') {
            return '<a href="https://www.upyun.com/?utm_source=lianmeng&utm_medium=referral" rel="noopener noreferrer" target="_blank">'
                 . '<img alt="upyun" src="' . htmlspecialchars(self::staticUrl('static/img/upyun.png'), ENT_QUOTES) . '"/></a>';
        }

        // 图片链接 [![alt](src)](url){newtab?}
        if (preg_match('/^\[!\[([^\]]*)\]\(([^)\s]+)\)\]\(([^)\s]+)\)\s*(\{(newtab|_blank)\})?\s*$/', $line, $m)) {
            $alt    = htmlspecialchars($m[1], ENT_QUOTES);
            $src    = htmlspecialchars($m[2], ENT_QUOTES);
            $url    = htmlspecialchars($m[3], ENT_QUOTES);
            $newtab = !empty($m[4]);
            $target = $newtab ? ' target="_blank" rel="noopener noreferrer"' : '';
            return '<a href="' . $url . '"' . $target . '><img alt="' . $alt . '" src="' . $src . '"/></a>';
        }

        // 文本链接 [text](url){newtab?}
        if (preg_match('/^\[([^\]]+)\]\(([^)\s]+)\)\s*(\{(newtab|_blank)\})?\s*$/', $line, $m)) {
            $text   = htmlspecialchars($m[1], ENT_QUOTES);
            $url    = htmlspecialchars($m[2], ENT_QUOTES);
            $newtab = !empty($m[3]);
            $target = $newtab ? ' target="_blank" rel="noopener noreferrer"' : '';
            return '<a href="' . $url . '"' . $target . '>' . $text . '</a>';
        }

        // 图片 ![alt](src)
        if (preg_match('/^!\[([^\]]*)\]\(([^)\s]+)\)\s*$/', $line, $m)) {
            $alt = htmlspecialchars($m[1], ENT_QUOTES);
            $src = htmlspecialchars($m[2], ENT_QUOTES);
            return '<img alt="' . $alt . '" src="' . $src . '"/>';
        }

        // 原始 HTML（允许 <a>/<img>/<span> 标签），可追加 {newtab}
        if (preg_match('/^(<(?:a|img|span)\s[^>]*>.*<\/(?:a|img|span)>|<img\s[^>]*\/?>)\s*(\{(newtab|_blank)\})?\s*$/is', $line, $m)) {
            $html = $m[1];
            $newtab = !empty($m[2]);
            if ($newtab) {
                // 为 <a> 标签添加 target="_blank" rel="noopener noreferrer"
                $html = preg_replace(
                    '/<a(\s)/i',
                    '<a target="_blank" rel="noopener noreferrer"$1',
                    $html,
                    1
                );
            }
            return $html;
        }

        // 纯文本
        return '<span>' . htmlspecialchars($line, ENT_QUOTES) . '</span>';
    }

    /**
     * 渲染整个自定义底部内容。
     * 若 footerCustom 为空，则回退到 legacy 行为（拼接 enableUPYUNLOGO + footerLOGO）。
     *
     * 支持容器指令（单独占一行）：
     *   {grid:cols=N}              指定 N 列，行数随内容自适应
     *   {grid:cols=N,rows=M}       指定 N 列 M 行
     * 容器指令必须在使用 {cell:...} 之前出现；指令本身不输出。
     *
     * @return String
     */
    public static function renderFooterCustom()
    {
        $custom = self::$config['footerCustom'];
        if ($custom === null || trim($custom) === '') {
            // 兼容旧设置
            return self::getFooterLogos();
        }
        $lines = preg_split('/\r\n|\r|\n/', $custom);

        $gridCols = 0;
        $gridRows = 0;
        $items    = '';
        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '')
                continue;
            // 容器指令：{grid:cols=N[,rows=M]}
            if (preg_match('/^\{grid:\s*cols\s*=\s*(\d+)(?:\s*,\s*rows\s*=\s*(\d+))?\s*\}$/i', $trim, $gm)) {
                $gridCols = max(1, (int)$gm[1]);
                if (isset($gm[2]) && $gm[2] !== '')
                    $gridRows = max(1, (int)$gm[2]);
                continue;
            }
            $rendered = self::renderFooterCustomItem($trim);
            if ($rendered !== '')
                $items .= $rendered;
        }

        if ($items === '')
            return '';

        if ($gridCols > 0) {
            $styleParts = array('--footer-grid-cols: ' . $gridCols);
            if ($gridRows > 0) {
                $styleParts[] = '--footer-grid-rows: ' . $gridRows;
                $styleParts[] = 'grid-template-rows: repeat(' . $gridRows . ', auto)';
            }
            $style = htmlspecialchars(implode('; ', $styleParts), ENT_QUOTES);
            $rowsAttr = $gridRows > 0 ? ' data-rows="' . $gridRows . '"' : '';
            return '<div class="footer-grid" data-cols="' . $gridCols . '"' . $rowsAttr
                 . ' style="' . $style . '">' . $items . '</div>';
        }
        return $items;
    }

    /**
     * 获取静态资源路径
     *
     * @param String $path
     * @return string
     */
    public static function staticUrl($path)
    {
        if (self::$config['cdn'] == 'local' || self::$config['cdn'] == '')
            return self::$themeUrl . $path;
        else if (self::$config['cdn'] == 'jsdelivr')
            return 'https://cdn.jsdelivr.net/gh/youranreus/G@v' . self::$version . '/' . $path;
        else if (self::$config['cdn'] == 'sourcestorage')
            return 'https://source.ahdark.com/typecho/theme/G-theme/' . self::$version . '/' . $path;
        else if (self::$config['cdn'] == 'jsdfastly')
            return 'https://fastly.jsdelivr.net/gh/youranreus/G@v' . self::$version . '/' . $path;
        else if (self::$config['cdn'] == 'jsdgcore')
            return 'https://gcore.jsdelivr.net/gh/youranreus/G@v' . self::$version . '/' . $path;
        else
            return self::$config['cdn'] . $path;
    }

    /**
     * 获取语义化修改时间
     *
     * @param string $modified
     * @param string $created
     * @return string
     */
    public static function getModifiedDate($modified, $created)
    {
        return $modified == $created ? GI18n::t('post.not_modified') : GI18n::t('post.last_modified') . self::getSemanticDate($modified);
    }

    /**
     * 获取语义化日期
     *
     * @param string $date
     * @return string
     */
    public static function getSemanticDate($date)
    {
        $now = time();
        $sub = $now - $date;

        if ($sub < 60)
            return GI18n::t('date.seconds_ago', $sub);
        else if ($sub < 3600)
            return GI18n::t('date.minutes_ago', (int)($sub / 60));
        else if ($sub < 86400)
            return GI18n::t('date.hours_ago', (int)($sub / 3600));
        else
            return GI18n::t('date.days_ago', (int)($sub / 86400));
    }

    /**
     * 解析文章内容
     *
     * @param string $content
     * @return string
     */
    public static function analyzeContent($content)
    {
        $content = self::analyzeMeme($content);
        return do_shortcode($content);
    }

    /**
     * 解析文字中的表情包
     *
     * @param string $content
     * @return string
     */
    public static function analyzeMeme($content)
    {
        //@(xx)格式表情
        $result = preg_replace('#@\((.*?)\)#', '<img alt="$1" src="'.G::staticUrl('static/img/bq/paopao').'/$1.png" class="bq" />', $content);
        //mirage格式表情 （原神，小黄脸）
        // 修改：category 和 name 都不能包含冒號
        $result = preg_replace_callback('/(?<![a-zA-Z0-9_])\:\:([^:]+?)\:([^:]+?)\:\:(?![a-zA-Z0-9_])/',function($matches){
            $img = preg_replace('/%/', '', urlencode($matches[2]));
            return '<img alt="'.$matches[2].'" src="'.self::staticUrl('static/img/bq/'.$matches[1].'/'.$img).'.png" class="bq" />';
        },$result);
        $result = preg_replace_callback('#\#\((.*?)\)#',function($matches) {
            $emotionText = substr(substr($matches[0], 0, -1), 2);
            $url = "<img class='bq bq-aru' alt='".$emotionText."' src='https://cdn.jsdelivr.net/gh/youranreus/R/W/bq/aru/".urlencode($emotionText).".png'/>";
            $url = preg_replace('/%/', '', $url);
            return $url;
        }, $result);
        return $result;
    }

    /**
     * 输出文章概要
     *
     * @param string $content
     * @param Integer $limit
     * @return string
     */
    public static function excerpt($content, $limit)
    {
        $result = mb_substr($content, 0, $limit);
        $result = self::analyzeMeme($result);
        $result = preg_replace('/\[[^\]]*\]/', '', $result);
        return strip_tags($result);
    }

    /**
     * 获取表情包url
     *
     * @param String $path
     * @param String $name
     * @return String
     */
    public static function MemeUrl($path, $name) 
    {
        return self::staticUrl($path.preg_replace('/%/', '', urlencode($name)));
    }

    /**
     * 修复评论锚点
     *
     * @param object $archive
     * @return string
     */
    public static function Comment_hash_fix($archive)
    {
        return "<script type=\"text/javascript\">
        (function () {
            window.TypechoComment = {
                dom : function (id) {
                    return document.getElementById(id);
                },
                create : function (tag, attr) {
                    var el = document.createElement(tag);
                    for (var key in attr) {
                        el.setAttribute(key, attr[key]);
                    }
                    return el;
                },
                reply : function (cid, coid) {
                    var comment = this.dom(cid), parent = comment.parentNode,
                        response = this.dom('" . $archive->respondId . "'), input = this.dom('comment-parent'),
                        form = 'form' == response.tagName ? response : response.getElementsByTagName('form')[0],
                        textarea = response.getElementsByTagName('textarea')[0];
                    if (null == input) {
                        input = this.create('input', {
                            'type' : 'hidden',
                            'name' : 'parent',
                            'id'   : 'comment-parent'
                        });
                        form.appendChild(input);
                    }
                    input.setAttribute('value', coid);
                    if (null == this.dom('comment-form-place-holder')) {
                        var holder = this.create('div', {
                            'id' : 'comment-form-place-holder'
                        });
                        response.parentNode.insertBefore(holder, response);
                    }
                    comment.appendChild(response);
                    this.dom('cancel-comment-reply-link').style.display = '';
                    if (null != textarea && 'text' == textarea.name) {
                        textarea.focus();
                    }
                    return false;
                },
                cancelReply : function () {
                    var response = this.dom('$archive->respondId'),
                    holder = this.dom('comment-form-place-holder'), input = this.dom('comment-parent');
                    if (null != input) {
                        input.parentNode.removeChild(input);
                    }
                    if (null == holder) {
                        return true;
                    }
                    this.dom('cancel-comment-reply-link').style.display = 'none';
                    holder.parentNode.insertBefore(response, holder);
                    return false;
                }
            };
        })();
        </script>
        ";
    }

    /**
     * 获取文章阅读数
     *
     * @param object $archive
     * @return int
     */
    public static function getPostView($archive)
    {
        $cid = $archive->cid;
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        if (!array_key_exists('views', $db->fetchRow($db->select()->from('table.contents')))) {
            $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `views` INT(10) DEFAULT 0;');
            return 0;
        }

        $row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid));

        if ($archive->is('single')) {
            $views = Typecho_Cookie::get('extend_contents_views');

            if (empty($views))
                $views = array();
            else
                $views = explode(',', $views);

            if (!in_array($cid, $views)) {
                $db->query($db->update('table.contents')->rows(array('views' => (int)$row['views'] + 1))->where('cid = ?', $cid));
                array_push($views, $cid);
                $views = implode(',', $views);
                Typecho_Cookie::set('extend_contents_views', $views); //记录查看cookie
            }
        }
        return $row['views'];
    }

    /**
     * 获取点赞数
     * by MisterMa
     *
     * @param int $cid
     * @return array
     */
    public static function agreeNum($cid)
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        if (!array_key_exists('agree', $db->fetchRow($db->select()->from('table.contents'))))
            $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `agree` INT(10) NOT NULL DEFAULT 0;');

        $agree = $db->fetchRow($db->select('table.contents.agree')->from('table.contents')->where('cid = ?', $cid));
        $AgreeRecording = Typecho_Cookie::get('typechoAgreeRecording');
        if (empty($AgreeRecording))
            Typecho_Cookie::set('typechoAgreeRecording', json_encode(array(0)));

        return array(
            'agree' => $agree['agree'],
            'recording' => in_array($cid, json_decode(Typecho_Cookie::get('typechoAgreeRecording')))
        );
    }

    /**
     * 点赞
     * by MisterMa
     *
     * @param int $cid
     * @return int
     */
    public static function agree($cid)
    {
        $db = Typecho_Db::get();
        $agree = $db->fetchRow($db->select('table.contents.agree')->from('table.contents')->where('cid = ?', $cid));

        $agreeRecording = Typecho_Cookie::get('typechoAgreeRecording');
        if (empty($agreeRecording))
            Typecho_Cookie::set('typechoAgreeRecording', json_encode(array($cid)));
        else {
            $agreeRecording = json_decode($agreeRecording);
            if (in_array($cid, $agreeRecording))
                return $agree['agree'];
            array_push($agreeRecording, $cid);
            Typecho_Cookie::set('typechoAgreeRecording', json_encode($agreeRecording));
        }

        $db->query($db->update('table.contents')->rows(array('agree' => (int)$agree['agree'] + 1))->where('cid = ?', $cid));
        $agree = $db->fetchRow($db->select('table.contents.agree')->from('table.contents')->where('cid = ?', $cid));
        return $agree['agree'];
    }

    /**
     * 获取文章标题
     *
     * @param int $id
     * @return string
     */
    public static function getTitleByID($id)
    {
        $db = Typecho_Db::get();
        $result = $db->fetchAll($db->select()->from('table.contents')
            ->where('status = ?', 'publish')
            ->where('type = ?', 'post')
            ->where('cid = ?', $id)
        );
        if ($result) {
            $i = 1;
            foreach ($result as $val) {
                $val = Typecho_Widget::widget('Widget_Abstract_Contents')->push($val);
                return htmlspecialchars($val['title']);
            }
        } else {
            $result = $db->fetchAll($db->select()->from('table.contents')
                ->where('status = ?', 'publish')
                ->where('type = ?', 'page')
                ->where('cid = ?', $id)
            );
            if ($result) {
                $i = 1;
                foreach ($result as $val) {
                    $val = Typecho_Widget::widget('Widget_Abstract_Contents')->push($val);
                    return htmlspecialchars($val['title']);
                }
            } else
                return '标题获取失败';
        }
    }

    /**
     * 点赞小组件
     *
     * @param string $action
     * @return string
     */
    public static function DYLM($action)
    {
        $db = Typecho_Db::get();
        $data = $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'G:likes'));

        if($data == NULL) {
            $insert = $db->insert('table.options')->rows(['name'=> 'G:likes', "user"=> '0', "value" => "0"]);
            $db->query($insert);
            $data = $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'G:likes'));
        }

        if($action == 'query')
        {
            return (int)$data['value'];
        }
        else if ($action == 'add')
        {
            $update = $db->update('table.options')->rows(["value"=> ((int)$data['value']) + 1])->where('name = ?', 'G:likes');
            return $db->query($update) == 1 ? 'success' : 'error';
        }

        return 'error param';
    }

    /**
     * 随机文章
     *
     * @param integer $limit
     * @return array
     */
    public static function randomArticle($limit = 5)
    {
        $db = Typecho_Db::get();
        $adapterName = $db->getAdapterName();
        if($adapterName == 'pgsql' || $adapterName == 'Pdo_Pgsql' || $adapterName == 'Pdo_SQLite' || $adapterName == 'SQLite'){
            $order_by = 'RANDOM()';
        }else{
            $order_by = 'RAND()';
        }
        $sql = $db->select()->from('table.contents')
            ->where('status = ?','publish')
            ->where('table.contents.created <= ?', time())
            ->where('type = ?', 'post')
            ->limit($limit)
            ->order($order_by);

        $result = $db->fetchAll($sql);
        for($i = 0; $i < $limit; $i++)
            $result[$i] = self::getArticleInfo($result[$i]['cid']);

        return $result;
    }
    
    /**
     * 通过cid获取文章信息
     *
     * @param string|integer $cid
     * @return array
     */
    public static function getArticleInfo($cid)
    {
        return Helper::widgetById('Contents', $cid);
    }
}
