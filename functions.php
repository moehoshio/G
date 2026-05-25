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

/**
 * Inline CSS + JS for the admin color palette picker.
 *
 * Augments any input with class "g-color-input" with:
 *  - a preset swatch row
 *  - a native HTML5 <input type="color"> picker
 *  - a multi-stop gradient builder (renders the original text input value
 *    as comma-separated hex colours: e.g. "#07F, #F09, #FC0")
 *  - an "Auto" checkbox for inputs with class "g-color-optional"
 *
 * Returns a string ready to be echoed inside a Typecho admin page.
 */
function gPalettePickerAssets()
{
    ob_start();
    ?>
    <style>
    .g-palette { margin: 6px 0 14px 0; padding: 10px 12px; border: 1px solid #e3e3e3; border-radius: 6px; background: #fafafa; font-size: 13px; }
    .g-palette-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 8px; }
    .g-palette-row:last-child { margin-bottom: 0; }
    .g-palette-label { color: #666; min-width: 64px; }
    .g-palette-swatches { display: flex; flex-wrap: wrap; gap: 6px; }
    .g-palette-swatch { width: 22px; height: 22px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.15); cursor: pointer; padding: 0; }
    .g-palette-swatch:hover { transform: scale(1.1); }
    .g-palette-preview { width: 80px; height: 28px; border: 1px solid #ccc; border-radius: 4px; }
    .g-palette-stops { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    .g-palette-stop { display: inline-flex; align-items: center; gap: 4px; padding: 2px 4px; border: 1px solid #ddd; border-radius: 4px; background: #fff; }
    .g-palette-stop input[type=color] { width: 28px; height: 24px; border: none; background: none; padding: 0; cursor: pointer; }
    .g-palette-stop button { border: none; background: transparent; color: #c33; cursor: pointer; padding: 0 2px; }
    .g-palette-actions button { margin-right: 6px; padding: 2px 8px; font-size: 12px; cursor: pointer; }
    .g-palette-auto { margin-left: 4px; }
    .g-palette[data-auto="1"] .g-palette-row.g-palette-editor { opacity: 0.45; pointer-events: none; }
    </style>
    <script>
    (function(){
        var PRESETS = ['#07F','#3399FF','#00C49A','#E74C3C','#F39C12','#9B59B6','#34495E','#6A6A6A','#FFFFFF','#000000'];

        function parseValue(val){
            val = (val || '').trim();
            if (val === '' || val.toLowerCase() === 'auto') return { auto: true, stops: [] };
            var stops = val.split(',').map(function(s){ return s.trim(); }).filter(function(s){ return s.length > 0; });
            return { auto: false, stops: stops };
        }
        function toHex(v){
            if (!v) return '#07F';
            v = v.trim();
            if (v[0] === '#') return v;
            // Map a few common named/rgb forms to a usable hex; fall back to default.
            var m = v.match(/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
            if (m) {
                return '#' + [m[1],m[2],m[3]].map(function(n){
                    var s = parseInt(n,10).toString(16);
                    return s.length === 1 ? '0'+s : s;
                }).join('').toUpperCase();
            }
            return '#07F';
        }
        function serialize(state){
            if (state.auto) return 'auto';
            if (!state.stops.length) return '';
            return state.stops.join(', ');
        }
        function gradientCss(stops){
            if (!stops.length) return 'transparent';
            if (stops.length === 1) return stops[0];
            return 'linear-gradient(135deg, ' + stops.join(', ') + ')';
        }

        function buildWidget(input){
            var optional = input.classList.contains('g-color-optional');
            var state = parseValue(input.value);
            if (!state.auto && !state.stops.length) state.stops = ['#07F'];

            var box = document.createElement('div');
            box.className = 'g-palette';

            // --- Preset swatch row ---
            var presetRow = document.createElement('div');
            presetRow.className = 'g-palette-row';
            var presetLabel = document.createElement('span');
            presetLabel.className = 'g-palette-label';
            presetLabel.textContent = '<?php echo addslashes(GI18n::t("config.palette_preset")); ?>';
            presetRow.appendChild(presetLabel);

            var swatches = document.createElement('div');
            swatches.className = 'g-palette-swatches';
            PRESETS.forEach(function(c){
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'g-palette-swatch';
                b.style.background = c;
                b.title = c;
                b.addEventListener('click', function(){
                    state.auto = false;
                    state.stops = [c];
                    sync();
                });
                swatches.appendChild(b);
            });
            presetRow.appendChild(swatches);
            box.appendChild(presetRow);

            // --- Auto toggle (optional colors only) ---
            if (optional) {
                var autoRow = document.createElement('div');
                autoRow.className = 'g-palette-row';
                var autoLabel = document.createElement('label');
                autoLabel.className = 'g-palette-auto';
                var autoCb = document.createElement('input');
                autoCb.type = 'checkbox';
                autoCb.checked = !!state.auto;
                autoCb.addEventListener('change', function(){
                    state.auto = autoCb.checked;
                    if (!state.auto && !state.stops.length) state.stops = ['#07F'];
                    sync();
                });
                autoLabel.appendChild(autoCb);
                autoLabel.appendChild(document.createTextNode(' <?php echo addslashes(GI18n::t("config.palette_auto")); ?>'));
                autoRow.appendChild(autoLabel);
                box.appendChild(autoRow);
            }

            // --- Gradient stops editor ---
            var editorRow = document.createElement('div');
            editorRow.className = 'g-palette-row g-palette-editor';
            var editorLabel = document.createElement('span');
            editorLabel.className = 'g-palette-label';
            editorLabel.textContent = '<?php echo addslashes(GI18n::t("config.palette_stops")); ?>';
            editorRow.appendChild(editorLabel);

            var stopsWrap = document.createElement('div');
            stopsWrap.className = 'g-palette-stops';
            editorRow.appendChild(stopsWrap);

            function renderStops(){
                stopsWrap.innerHTML = '';
                state.stops.forEach(function(c, idx){
                    var stop = document.createElement('span');
                    stop.className = 'g-palette-stop';
                    var picker = document.createElement('input');
                    picker.type = 'color';
                    picker.value = toHex(c);
                    picker.addEventListener('input', function(){
                        state.stops[idx] = picker.value.toUpperCase();
                        sync(true);
                    });
                    stop.appendChild(picker);
                    if (state.stops.length > 1) {
                        var del = document.createElement('button');
                        del.type = 'button';
                        del.textContent = '×';
                        del.title = '<?php echo addslashes(GI18n::t("config.palette_remove_stop")); ?>';
                        del.addEventListener('click', function(){
                            state.stops.splice(idx, 1);
                            sync();
                        });
                        stop.appendChild(del);
                    }
                    stopsWrap.appendChild(stop);
                });
            }

            var actions = document.createElement('div');
            actions.className = 'g-palette-actions';
            var addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.textContent = '+ <?php echo addslashes(GI18n::t("config.palette_add_stop")); ?>';
            addBtn.addEventListener('click', function(){
                state.auto = false;
                state.stops.push(state.stops.length ? state.stops[state.stops.length - 1] : '#07F');
                sync();
            });
            actions.appendChild(addBtn);
            editorRow.appendChild(actions);
            box.appendChild(editorRow);

            // --- Preview row ---
            var prevRow = document.createElement('div');
            prevRow.className = 'g-palette-row';
            var prevLabel = document.createElement('span');
            prevLabel.className = 'g-palette-label';
            prevLabel.textContent = '<?php echo addslashes(GI18n::t("config.palette_preview")); ?>';
            prevRow.appendChild(prevLabel);
            var preview = document.createElement('div');
            preview.className = 'g-palette-preview';
            prevRow.appendChild(preview);
            box.appendChild(prevRow);

            function sync(stopsOnly){
                if (!stopsOnly) renderStops();
                input.value = serialize(state);
                input.dispatchEvent(new Event('change'));
                preview.style.background = state.auto ? 'repeating-linear-gradient(45deg,#ddd,#ddd 6px,#fff 6px,#fff 12px)' : gradientCss(state.stops);
                box.setAttribute('data-auto', state.auto ? '1' : '0');
            }

            input.parentNode.insertBefore(box, input.nextSibling);
            renderStops();
            sync(true);
        }

        function init(){
            var inputs = document.querySelectorAll('input.g-color-input');
            for (var i = 0; i < inputs.length; i++) {
                if (inputs[i].dataset.gPaletteInit) continue;
                inputs[i].dataset.gPaletteInit = '1';
                try { buildWidget(inputs[i]); } catch(e) { /* ignore */ }
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

function themeConfig($form)
{
    echo "<link rel='stylesheet' href='".G::staticUrl('static/css/Admin/S.min.css')."'/>";
    echo gPalettePickerAssets();
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
    $themeColor->input->setAttribute('class', 'g-color-input g-color-required');
    $themeColor->input->setAttribute('data-g-color', 'themeColor');
    $form->addInput($themeColor);

    $secondaryColor = new Typecho_Widget_Helper_Form_Element_Text('secondaryColor', null, '#6A6A6A', _t(GI18n::t('config.secondary_color')), _t(GI18n::t('config.secondary_color_desc')));
    $secondaryColor->input->setAttribute('class', 'g-color-input g-color-required');
    $secondaryColor->input->setAttribute('data-g-color', 'secondaryColor');
    $form->addInput($secondaryColor);

    $alertColor = new Typecho_Widget_Helper_Form_Element_Text('alertColor', null, '#E74C3C', _t(GI18n::t('config.alert_color')), _t(GI18n::t('config.alert_color_desc')));
    $alertColor->input->setAttribute('class', 'g-color-input g-color-required');
    $alertColor->input->setAttribute('data-g-color', 'alertColor');
    $form->addInput($alertColor);

    $headerColor = new Typecho_Widget_Helper_Form_Element_Text('headerColor', null, 'auto', _t(GI18n::t('config.header_color')), _t(GI18n::t('config.header_color_desc')));
    $headerColor->input->setAttribute('class', 'g-color-input g-color-optional');
    $headerColor->input->setAttribute('data-g-color', 'headerColor');
    $form->addInput($headerColor);

    $hoverColor = new Typecho_Widget_Helper_Form_Element_Text('hoverColor', null, 'auto', _t(GI18n::t('config.hover_color')), _t(GI18n::t('config.hover_color_desc')));
    $hoverColor->input->setAttribute('class', 'g-color-input g-color-optional');
    $hoverColor->input->setAttribute('data-g-color', 'hoverColor');
    $form->addInput($hoverColor);

    $enableFrostedGlass = new Typecho_Widget_Helper_Form_Element_Radio('enableFrostedGlass', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '0', _t(GI18n::t('config.frosted_glass')), _t(GI18n::t('config.frosted_glass_desc')));
    $form->addInput($enableFrostedGlass);

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



