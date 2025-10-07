<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

require_once("libs/G.class.php");
require_once("libs/GEditor.class.php");
G::init();
Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('GEditor', 'reply2see');
Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('GEditor', 'reply2see');
Typecho_Plugin::factory('admin/write-post.php')->bottom = array('GEditor', 'addButton');
Typecho_Plugin::factory('admin/write-page.php')->bottom = array('GEditor', 'addButton');
Typecho_Plugin::factory('admin/write-post.php')->bottom = array('GEditor', 'wordCounter');
Typecho_Plugin::factory('admin/write-page.php')->bottom = array('GEditor', 'wordCounter');

/**
 * 是否存在备份
 */
function hasBackup($db) {
    return $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'theme:'.G::$themeBackup));
}

/**
 * 备份完成提示
 */
function backupNotice($msg, $refresh = true) {
    $content = $msg.''.($refresh ? '，即將自動刷新' : '');
    if ($refresh) {
        $url = Helper::options()->adminUrl.'options-theme.php';
        $content .= '
            <a href="'.$url.'">手動刷新</a>
            <script language="JavaScript">window.setTimeout("location=\''.$url.'\'", 2500);</script>
        ';
    }

    echo '<div class="backup-notice">'.$content.'</div>';
}

/**
 * 备份操作
 */
function makeBackup($db, $hasBackup) {
    $currentConfig = $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'theme:G'))['value'];
    $query = $hasBackup
        ? $db->update('table.options')->rows(array('value' => $currentConfig))->where('name = ?', 'theme:'.G::$themeBackup)
        : $db->insert('table.options')->rows(array('name' => 'theme:'.G::$themeBackup, 'user' => '0', 'value' => $currentConfig));
    
    $rows = $db->query($query);
    
    return ['msg' => $hasBackup ? '備份已經成功更新' : '備份成功', 'refresh' => true];
}

/**
 * 恢復備份
 */
function restoreBackup($db, $hasBackup) {
    if (!$hasBackup) 
        return ['msg' => '沒有模板備份數據，恢復不了哦！', 'refresh' => false];
    
    $backupConfig = $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'theme:'.G::$themeBackup))['value'];
    $update = $db->update('table.options')->rows(array('value' => $backupConfig))->where('name = ?', 'theme:G');
    $updateRows = $db->query($update);

    return ['msg' => '恢復成功', 'refresh' => true];
}

/**
 * 刪除備份
 */
function deleteBackup($db, $hasBackup) {
    if (!$hasBackup) 
        return ['msg' => '沒有模板備份數據哦', 'refresh' => false];

    $delete = $db->delete('table.options')->where('name = ?', 'theme:'.G::$themeBackup);
    $deletedRows = $db->query($delete);
    
    return ['msg' => '刪除成功', 'refresh' => true];
}

/**
 * 备份主方法
 */
function backup() {
    $db = Typecho_Db::get();
    $hasBackup = hasBackup($db);
    if (isset($_POST['type'])) {
        $result = [];
        switch($_POST['type']) {
            case '創建備份':
            case '更新備份':
                $result = makeBackup($db, $hasBackup);
                break;
            case '恢復備份':
                $result = restoreBackup($db, $hasBackup);
                break;
            case '刪除備份':
                $result = deleteBackup($db, $hasBackup);
                break;
            default:
                $result = ["msg" => "", "refresh" => false];
                break;
        }
        if ($result["msg"])
            backupNotice($result["msg"], $result["refresh"]);
    }
    echo '
        <div id="backup">
            <form class="protected Data-backup" action="?'.G::$themeBackup.'" method="post">
                <h4>數據備份</h4>
                <p style="opacity: 0.5">'.($hasBackup ? '當前已有備份' : '當前暫無備份').'，你可以選擇</p>
                <input type="submit" name="type" class="btn btn-s" value="'.($hasBackup ? '更新備份' : '創建備份').'" />&nbsp;&nbsp;
                '.($hasBackup ? '<input type="submit" name="type" class="btn btn-s" value="恢復備份" />&nbsp;&nbsp;' : '').'
                '.($hasBackup ? '<input type="submit" name="type" class="btn btn-s" value="刪除備份" />' : '').'
            </form>
        </div>
    ';
}

function themeConfig($form)
{
    echo "<link rel='stylesheet' href='".G::staticUrl('static/css/Admin/S.min.css')."'/>";
    echo "<h2>G主題設置</h2>";

    $favicon = new Typecho_Widget_Helper_Form_Element_Text('favicon', null, null, _t('站點 LOGO 地址'), _t('在這裡填入一個圖片 URL 地址, 以在網站標題前加上一個 LOGO'));
    $form->addInput($favicon);

    $buildYear = new Typecho_Widget_Helper_Form_Element_Text('buildYear', null, date('Y'), _t('建站年份'), _t('什麼時候開始建站的呀'));
    $form->addInput($buildYear);

    $cdn = new Typecho_Widget_Helper_Form_Element_Text('cdn', null, null, _t('是否開啟靜態資源cdn加速'), _t("填寫加速域名或者jsdelivr或者sourcestorage，留空則使用本地文件</br>注意: 新版本剛剛發布時，可能CDN不會及時更新"));
    $form->addInput($cdn);

    $icp = new Typecho_Widget_Helper_Form_Element_Text('icp', null, null, _t('ICP備案號'), _t('沒有可以不填哟'));
    $form->addInput($icp);

    $icpUrl = new Typecho_Widget_Helper_Form_Element_Text('icpUrl', null, 'https://beian.miit.gov.cn', _t('備案號指向鏈接'), _t('默認指向工信部'));
    $form->addInput($icpUrl);

    $background = new Typecho_Widget_Helper_Form_Element_Text('background', null, null, _t('背景圖片'), _t('可填顏色代碼或者圖片url'));
    $form->addInput($background);

    $repeatBackground = new Typecho_Widget_Helper_Form_Element_Radio('repeatBackground', array(
        '1' => _t('開啟'),
        '0' => _t('關閉')
    ), '0', _t('重複元素背景圖片'), _t('默認關閉'));
    $form->addInput($repeatBackground);

    $themeColor = new Typecho_Widget_Helper_Form_Element_Text('themeColor', null, '#07F', _t('主題色'), _t('一般在鏈接、按鈕的顏色中體現'));
    $form->addInput($themeColor);

    $headerColor = new Typecho_Widget_Helper_Form_Element_Text('headerColor', null, '#6A6A6A', _t('頭部色'), _t('想要一朵綠帽子不？'));
    $form->addInput($headerColor);

    $themeRadius = new Typecho_Widget_Helper_Form_Element_Text('themeRadius', null, '30px', _t('主題圓角'), _t('圓還是方，由你來定'));
    $form->addInput($themeRadius);

    $defaultBanner = new Typecho_Widget_Helper_Form_Element_Text('defaultBanner', null, null, _t('默認頭圖'), _t('填入圖片API時，可以使用{random}來替換生成一個隨機字符串以達到隨機圖片得效果'));
    $form->addInput($defaultBanner);

    $profileAvatar = new Typecho_Widget_Helper_Form_Element_Text('profileAvatar', null, null, _t('側邊欄頭像'), _t('https://...'));
    $form->addInput($profileAvatar);

    $profileBG = new Typecho_Widget_Helper_Form_Element_Text('profileBG', null, null, _t('側邊欄背景'), _t('https://...'));
    $form->addInput($profileBG);

    $profileDes = new Typecho_Widget_Helper_Form_Element_Text('profileDes', null, null, _t('側邊欄簡介'), _t('儘量簡潔'));
    $form->addInput($profileDes);

    $profilePhoto = new Typecho_Widget_Helper_Form_Element_Text('profilePhoto', null, null, _t('側邊欄小相片'), _t('https://'));
    $form->addInput($profilePhoto);

    $profileVideo = new Typecho_Widget_Helper_Form_Element_Text('profileVideo', null, null, _t('側邊欄小視頻'), _t('https://'));
    $form->addInput($profileVideo);

    $profilePhotoDes = new Typecho_Widget_Helper_Form_Element_Text('profilePhotoDes', null, null, _t('側邊欄圖片描述'), _t('關於圖片/視頻的簡短描述'));
    $form->addInput($profilePhotoDes);

    $footerLOGO = new Typecho_Widget_Helper_Form_Element_Text('footerLOGO', null, null, _t('底部左側logo'), _t('填寫logo圖片鏈接，用,分割'));
    $form->addInput($footerLOGO);

    $sponsorIMG = new Typecho_Widget_Helper_Form_Element_Text('sponsorIMG', null, null, _t('贊助二維碼圖片'), _t('填寫後會在文章底部添加一個贊助按鈕'));
    $form->addInput($sponsorIMG);

    $headerBackground = new Typecho_Widget_Helper_Form_Element_Text('headerBackground', null, null, _t('頭部背景圖'), _t('填寫後會在站點頭部添加一個半透明的背景圖'));
    $form->addInput($headerBackground);

    $autoNightSpan = new Typecho_Widget_Helper_Form_Element_Text('autoNightSpan', null, '23-6', _t('自動夜間模式時間段'), _t('24小時制，當前晚上x點到第二天早上y點視為夜間，需要自動開啟夜間模式，例: 23-6'));
    $form->addInput($autoNightSpan);

    $commentType = new Typecho_Widget_Helper_Form_Element_Radio('commentType', array(
        '1' => _t('開啟'),
        '0' => _t('關閉')
    ), '1', _t('評論展示開關'), _t('默認開啟'));
    $form->addInput($commentType);

    $autoNightMode = new Typecho_Widget_Helper_Form_Element_Radio('autoNightMode', array(
        '3' => _t('跟隨系統'),
        '2' => _t('自定義時間段'),
        '1' => _t('同時開啟'),
        '0' => _t('關閉')
    ), '3', _t('自動夜間模式控制模式'), _t('默認為跟隨系統'));
    $form->addInput($autoNightMode);

    $enableDefaultTOC = new Typecho_Widget_Helper_Form_Element_Radio('enableDefaultTOC', array(
        '1' => _t('開啟'),
        '0' => _t('關閉')
    ), '0', _t('文章目錄是否默認開啟'), _t('默認否'));
    $form->addInput($enableDefaultTOC);

    $enableUPYUNLOGO = new Typecho_Widget_Helper_Form_Element_Radio('enableUPYUNLOGO', array(
        '1' => _t('開啟'),
        '0' => _t('關閉')
    ), '0', _t('是否開啟又拍雲聯盟圖標展示'), _t('默認關閉'));
    $form->addInput($enableUPYUNLOGO);

    $themeShadow = new Typecho_Widget_Helper_Form_Element_Radio('themeShadow', array(
        '1' => _t('開啟'),
        '0' => _t('關閉')
    ), '1', _t('是否開啟主題陰影'), _t('默認開啟'));
    $form->addInput($themeShadow);

    $enableKatex = new Typecho_Widget_Helper_Form_Element_Radio('enableKatex', array(
        '1' => _t('開啟'),
        '0' => _t('關閉')
    ), '0', _t('是否開啟Katex數學公式解析'), _t('默認關閉'));
    $form->addInput($enableKatex);

    $autoBanner = new Typecho_Widget_Helper_Form_Element_Radio('autoBanner', array(
        '1' => _t('開啟'),
        '0' => _t('關閉')
    ), '1', _t('自動獲取第一張圖片作為頭圖'), _t('默認開啟'));
    $form->addInput($autoBanner);

    $enableIndexPage = new Typecho_Widget_Helper_Form_Element_Radio('enableIndexPage', array(
        '1' => _t('使用'),
        '0' => _t('不使用')
    ), '0', _t('是否使用獨立頁面作首頁'), _t('默認不使用'));
    $form->addInput($enableIndexPage);

    $enableHeaderSearch = new Typecho_Widget_Helper_Form_Element_Radio('enableHeaderSearch', array(
        '1' => _t('開啟'),
        '0' => _t('關閉')
    ), '0', _t('是否在頭部添加搜尋開關'), _t('默認不打開,需要配合exsearch插件使用'));
    $form->addInput($enableHeaderSearch);

    $articleStyle = new Typecho_Widget_Helper_Form_Element_Radio('articleStyle', array(
        '2' => _t('大圖'),
        '1' => _t('單列'),
        '0' => _t('雙列')
    ), '0', _t('首頁樣式'), _t('默認為雙列'));
    $form->addInput($articleStyle);

    $defaultArticlePath = new Typecho_Widget_Helper_Form_Element_Text('defaultArticlePath', null, 'index.php/blog', _t('默認頭部文章路徑'), _t('前面不需要加/'));
    $form->addInput($defaultArticlePath);

    $customWidgets = new Typecho_Widget_Helper_Form_Element_Textarea('customWidgets', null, null, _t('側邊欄小組件配置'), _t(''));
    $form->addInput($customWidgets);

    $customCSS = new Typecho_Widget_Helper_Form_Element_Textarea('customCSS', null, null, _t('自定義CSS'), _t(''));
    $form->addInput($customCSS);

    $customHeaderJS = new Typecho_Widget_Helper_Form_Element_Textarea('customHeaderJS', null, null, _t('自定義頭部JS'), _t('head標籤中'));
    $form->addInput($customHeaderJS);

    $customFooterJS = new Typecho_Widget_Helper_Form_Element_Textarea('customFooterJS', null, null, _t('自定義底部JS'), _t('body結束前'));
    $form->addInput($customFooterJS);

    $customPjaxCallback = new Typecho_Widget_Helper_Form_Element_Textarea('customPjaxCallback', null, null, _t('自定義Pjax回調函數'), _t('如果你不知道這個是啥，留著就好'));
    $form->addInput($customPjaxCallback);

    $advanceSetting = new Typecho_Widget_Helper_Form_Element_Textarea('advanceSetting', null, null, _t('高級設置'), _t('看著就很高級'));
    $form->addInput($advanceSetting);

    backup();
}


function themeFields($layout)
{
    $imgurl = new Typecho_Widget_Helper_Form_Element_Text('imgurl', null, null, _t('文章頭圖地址'), _t('在這裡填入一個圖片URL地址'));
    $layout->addItem($imgurl);

    $headerDisplay = new Typecho_Widget_Helper_Form_Element_Radio('headerDisplay', array(
        '1' => _t('顯示'),
        '0' => _t('不顯示')
    ), '0', _t('(獨立頁面)是否顯示在頭部導航欄'), _t('默認不顯示'));
    $layout->addItem($headerDisplay);

    $enableComment = new Typecho_Widget_Helper_Form_Element_Radio('enableComment', array(
        '1' => _t('顯示'),
        '0' => _t('不顯示')
    ), '0', _t('(獨立頁面)是否顯示評論框'), _t('默認不顯示'));
    $layout->addItem($enableComment);
}
