<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

require_once("libs/G.class.php");
require_once("libs/GEditor.class.php");
require_once("libs/I18n.class.php");
G::init();
GI18n::init();
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
    $content = $msg.''.($refresh ? GI18n::t('backup.auto_refresh') : '');
    if ($refresh) {
        $url = Helper::options()->adminUrl.'options-theme.php';
        $content .= '
            <a href="'.$url.'">'.GI18n::t('backup.manual_refresh').'</a>
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
    
    return ['msg' => $hasBackup ? GI18n::t('backup.updated') : GI18n::t('backup.created'), 'refresh' => true];
}

/**
 * 恢复备份
 */
function restoreBackup($db, $hasBackup) {
    if (!$hasBackup) 
        return ['msg' => GI18n::t('backup.no_data_restore'), 'refresh' => false];
    
    $backupConfig = $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'theme:'.G::$themeBackup))['value'];
    $update = $db->update('table.options')->rows(array('value' => $backupConfig))->where('name = ?', 'theme:G');
    $updateRows = $db->query($update);

    return ['msg' => GI18n::t('backup.restored'), 'refresh' => true];
}

/**
 * 删除备份
 */
function deleteBackup($db, $hasBackup) {
    if (!$hasBackup) 
        return ['msg' => GI18n::t('backup.no_data_delete'), 'refresh' => false];

    $delete = $db->delete('table.options')->where('name = ?', 'theme:'.G::$themeBackup);
    $deletedRows = $db->query($delete);
    
    return ['msg' => GI18n::t('backup.deleted'), 'refresh' => true];
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
            case GI18n::t('backup.create'):
            case GI18n::t('backup.update'):
                $result = makeBackup($db, $hasBackup);
                break;
            case GI18n::t('backup.restore'):
                $result = restoreBackup($db, $hasBackup);
                break;
            case GI18n::t('backup.delete'):
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
                <h4>'.GI18n::t('backup.title').'</h4>
                <p style="opacity: 0.5">'.($hasBackup ? GI18n::t('backup.has_backup') : GI18n::t('backup.no_backup')).GI18n::t('backup.choose').'</p>
                <input type="submit" name="type" class="btn btn-s" value="'.($hasBackup ? GI18n::t('backup.update') : GI18n::t('backup.create')).'" />&nbsp;&nbsp;
                '.($hasBackup ? '<input type="submit" name="type" class="btn btn-s" value="'.GI18n::t('backup.restore').'" />&nbsp;&nbsp;' : '').'
                '.($hasBackup ? '<input type="submit" name="type" class="btn btn-s" value="'.GI18n::t('backup.delete').'" />' : '').'
            </form>
        </div>
    ';
}

function themeConfig($form)
{
    echo "<link rel='stylesheet' href='".G::staticUrl('static/css/Admin/S.min.css')."'/>";
    echo "<h2>".GI18n::t('config.title')."</h2>";

    $lang = new Typecho_Widget_Helper_Form_Element_Select('lang', array_merge(
        ['' => GI18n::t('config.auto_detect')],
        GI18n::$supportedLangs
    ), '', _t(GI18n::t('config.lang')), _t(GI18n::t('config.lang_desc')));
    $form->addInput($lang);

    $favicon = new Typecho_Widget_Helper_Form_Element_Text('favicon', null, null, _t(GI18n::t('config.favicon')), _t(GI18n::t('config.favicon_desc')));
    $form->addInput($favicon);

    $buildYear = new Typecho_Widget_Helper_Form_Element_Text('buildYear', null, date('Y'), _t(GI18n::t('config.build_year')), _t(GI18n::t('config.build_year_desc')));
    $form->addInput($buildYear);

    $cdn = new Typecho_Widget_Helper_Form_Element_Text('cdn', null, null, _t(GI18n::t('config.cdn')), _t(GI18n::t('config.cdn_desc')));
    $form->addInput($cdn);

    $icp = new Typecho_Widget_Helper_Form_Element_Text('icp', null, null, _t(GI18n::t('config.icp')), _t(GI18n::t('config.icp_desc')));
    $form->addInput($icp);

    $icpUrl = new Typecho_Widget_Helper_Form_Element_Text('icpUrl', null, 'https://beian.miit.gov.cn', _t(GI18n::t('config.icp_url')), _t(GI18n::t('config.icp_url_desc')));
    $form->addInput($icpUrl);

    $background = new Typecho_Widget_Helper_Form_Element_Text('background', null, null, _t(GI18n::t('config.background')), _t(GI18n::t('config.background_desc')));
    $form->addInput($background);

    $repeatBackground = new Typecho_Widget_Helper_Form_Element_Radio('repeatBackground', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '0', _t(GI18n::t('config.repeat_bg')), _t(GI18n::t('config.repeat_bg_desc')));
    $form->addInput($repeatBackground);

    $themeColor = new Typecho_Widget_Helper_Form_Element_Text('themeColor', null, '#07F', _t(GI18n::t('config.theme_color')), _t(GI18n::t('config.theme_color_desc')));
    $form->addInput($themeColor);

    $headerColor = new Typecho_Widget_Helper_Form_Element_Text('headerColor', null, '#6A6A6A', _t(GI18n::t('config.header_color')), _t(GI18n::t('config.header_color_desc')));
    $form->addInput($headerColor);

    $themeRadius = new Typecho_Widget_Helper_Form_Element_Text('themeRadius', null, '30px', _t(GI18n::t('config.theme_radius')), _t(GI18n::t('config.theme_radius_desc')));
    $form->addInput($themeRadius);

    $defaultBanner = new Typecho_Widget_Helper_Form_Element_Text('defaultBanner', null, null, _t(GI18n::t('config.default_banner')), _t(GI18n::t('config.default_banner_desc')));
    $form->addInput($defaultBanner);

    $profileAvatar = new Typecho_Widget_Helper_Form_Element_Text('profileAvatar', null, null, _t(GI18n::t('config.profile_avatar')), _t('https://...'));
    $form->addInput($profileAvatar);

    $profileBG = new Typecho_Widget_Helper_Form_Element_Text('profileBG', null, null, _t(GI18n::t('config.profile_bg')), _t('https://...'));
    $form->addInput($profileBG);

    $profileDes = new Typecho_Widget_Helper_Form_Element_Text('profileDes', null, null, _t(GI18n::t('config.profile_des')), _t(GI18n::t('config.profile_des_desc')));
    $form->addInput($profileDes);

    $profilePhoto = new Typecho_Widget_Helper_Form_Element_Text('profilePhoto', null, null, _t(GI18n::t('config.profile_photo')), _t('https://'));
    $form->addInput($profilePhoto);

    $profileVideo = new Typecho_Widget_Helper_Form_Element_Text('profileVideo', null, null, _t(GI18n::t('config.profile_video')), _t('https://'));
    $form->addInput($profileVideo);

    $profilePhotoDes = new Typecho_Widget_Helper_Form_Element_Text('profilePhotoDes', null, null, _t(GI18n::t('config.profile_photo_des')), _t(GI18n::t('config.profile_photo_des_desc')));
    $form->addInput($profilePhotoDes);

    $footerLOGO = new Typecho_Widget_Helper_Form_Element_Text('footerLOGO', null, null, _t(GI18n::t('config.footer_logo')), _t(GI18n::t('config.footer_logo_desc')));
    $form->addInput($footerLOGO);

    $sponsorIMG = new Typecho_Widget_Helper_Form_Element_Text('sponsorIMG', null, null, _t(GI18n::t('config.sponsor_img')), _t(GI18n::t('config.sponsor_img_desc')));
    $form->addInput($sponsorIMG);

    $headerBackground = new Typecho_Widget_Helper_Form_Element_Text('headerBackground', null, null, _t(GI18n::t('config.header_bg')), _t(GI18n::t('config.header_bg_desc')));
    $form->addInput($headerBackground);

    $autoNightSpan = new Typecho_Widget_Helper_Form_Element_Text('autoNightSpan', null, '23-6', _t(GI18n::t('config.auto_night_span')), _t(GI18n::t('config.auto_night_span_desc')));
    $form->addInput($autoNightSpan);

    $commentType = new Typecho_Widget_Helper_Form_Element_Radio('commentType', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '1', _t(GI18n::t('config.comment_switch')), _t(GI18n::t('config.comment_switch_desc')));
    $form->addInput($commentType);

    $autoNightMode = new Typecho_Widget_Helper_Form_Element_Radio('autoNightMode', array(
        '3' => _t(GI18n::t('config.follow_system')),
        '2' => _t(GI18n::t('config.custom_time')),
        '1' => _t(GI18n::t('config.both_enabled')),
        '0' => _t(GI18n::t('common.disable'))
    ), '3', _t(GI18n::t('config.auto_night_mode')), _t(GI18n::t('config.auto_night_mode_desc')));
    $form->addInput($autoNightMode);

    $enableDefaultTOC = new Typecho_Widget_Helper_Form_Element_Radio('enableDefaultTOC', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '0', _t(GI18n::t('config.enable_toc')), _t(GI18n::t('config.enable_toc_desc')));
    $form->addInput($enableDefaultTOC);

    $enableUPYUNLOGO = new Typecho_Widget_Helper_Form_Element_Radio('enableUPYUNLOGO', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '0', _t(GI18n::t('config.enable_upyun')), _t(GI18n::t('config.enable_upyun_desc')));
    $form->addInput($enableUPYUNLOGO);

    $themeShadow = new Typecho_Widget_Helper_Form_Element_Radio('themeShadow', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '1', _t(GI18n::t('config.theme_shadow')), _t(GI18n::t('config.theme_shadow_desc')));
    $form->addInput($themeShadow);

    $enableKatex = new Typecho_Widget_Helper_Form_Element_Radio('enableKatex', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '0', _t(GI18n::t('config.enable_katex')), _t(GI18n::t('config.enable_katex_desc')));
    $form->addInput($enableKatex);

    $autoBanner = new Typecho_Widget_Helper_Form_Element_Radio('autoBanner', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '1', _t(GI18n::t('config.auto_banner')), _t(GI18n::t('config.auto_banner_desc')));
    $form->addInput($autoBanner);

    $enableIndexPage = new Typecho_Widget_Helper_Form_Element_Radio('enableIndexPage', array(
        '1' => _t(GI18n::t('common.use')),
        '0' => _t(GI18n::t('common.not_use'))
    ), '0', _t(GI18n::t('config.enable_index_page')), _t(GI18n::t('config.enable_index_page_desc')));
    $form->addInput($enableIndexPage);

    $enableHeaderSearch = new Typecho_Widget_Helper_Form_Element_Radio('enableHeaderSearch', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '0', _t(GI18n::t('config.enable_search')), _t(GI18n::t('config.enable_search_desc')));
    $form->addInput($enableHeaderSearch);

    $articleStyle = new Typecho_Widget_Helper_Form_Element_Radio('articleStyle', array(
        '2' => _t(GI18n::t('config.style_large')),
        '1' => _t(GI18n::t('config.style_single')),
        '0' => _t(GI18n::t('config.style_double'))
    ), '0', _t(GI18n::t('config.article_style')), _t(GI18n::t('config.article_style_desc')));
    $form->addInput($articleStyle);

    $defaultArticlePath = new Typecho_Widget_Helper_Form_Element_Text('defaultArticlePath', null, 'index.php/blog', _t(GI18n::t('config.default_path')), _t(GI18n::t('config.default_path_desc')));
    $form->addInput($defaultArticlePath);

    $customWidgets = new Typecho_Widget_Helper_Form_Element_Textarea('customWidgets', null, null, _t(GI18n::t('config.custom_widgets')), _t(''));
    $form->addInput($customWidgets);

    $customCSS = new Typecho_Widget_Helper_Form_Element_Textarea('customCSS', null, null, _t(GI18n::t('config.custom_css')), _t(''));
    $form->addInput($customCSS);

    $customHeaderJS = new Typecho_Widget_Helper_Form_Element_Textarea('customHeaderJS', null, null, _t(GI18n::t('config.custom_header_js')), _t(GI18n::t('config.custom_header_js_desc')));
    $form->addInput($customHeaderJS);

    $customFooterJS = new Typecho_Widget_Helper_Form_Element_Textarea('customFooterJS', null, null, _t(GI18n::t('config.custom_footer_js')), _t(GI18n::t('config.custom_footer_js_desc')));
    $form->addInput($customFooterJS);

    $customPjaxCallback = new Typecho_Widget_Helper_Form_Element_Textarea('customPjaxCallback', null, null, _t(GI18n::t('config.custom_pjax')), _t(GI18n::t('config.custom_pjax_desc')));
    $form->addInput($customPjaxCallback);

    $advanceSetting = new Typecho_Widget_Helper_Form_Element_Textarea('advanceSetting', null, null, _t(GI18n::t('config.advance_setting')), _t(GI18n::t('config.advance_setting_desc')));
    $form->addInput($advanceSetting);

    backup();
}


function themeFields($layout)
{
    $imgurl = new Typecho_Widget_Helper_Form_Element_Text('imgurl', null, null, _t(GI18n::t('fields.banner')), _t(GI18n::t('fields.banner_desc')));
    $layout->addItem($imgurl);

    $headerDisplay = new Typecho_Widget_Helper_Form_Element_Radio('headerDisplay', array(
        '1' => _t(GI18n::t('common.show')),
        '0' => _t(GI18n::t('common.hide'))
    ), '0', _t(GI18n::t('fields.header_display')), _t(GI18n::t('fields.header_display_desc')));
    $layout->addItem($headerDisplay);

    $enableComment = new Typecho_Widget_Helper_Form_Element_Radio('enableComment', array(
        '1' => _t(GI18n::t('common.show')),
        '0' => _t(GI18n::t('common.hide'))
    ), '0', _t(GI18n::t('fields.enable_comment')), _t(GI18n::t('fields.enable_comment_desc')));
    $layout->addItem($enableComment);
}



