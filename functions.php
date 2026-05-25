<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

require_once("libs/G.class.php");
require_once("libs/GEditor.class.php");
require_once("libs/I18n.class.php");
G::init();
GI18n::init();

/**
 * Handle AJAX request to immediately persist the theme language setting.
 * Triggered via POST to `?GLangChange=1` with form field `lang`.
 * Only valid lang codes (or empty for auto-detect) are accepted.
 */
if (isset($_GET['GLangChange']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!class_exists('Typecho_Widget') || !method_exists('Typecho_Widget', 'widget')) {
        http_response_code(500);
        exit('error');
    }
    // Require an authenticated admin user (Typecho admin session).
    try {
        $user = Typecho_Widget::widget('Widget_User');
        if (!$user->hasLogin() || !$user->pass('administrator', true)) {
            http_response_code(403);
            exit('forbidden');
        }
    } catch (Exception $e) {
        http_response_code(403);
        exit('forbidden');
    }

    // Basic same-origin (Referer/Origin) check as CSRF defense.
    $siteUrl  = Helper::options()->siteUrl;
    $siteHost = parse_url($siteUrl, PHP_URL_HOST);
    $reqOrigin = '';
    if (!empty($_SERVER['HTTP_ORIGIN'])) {
        $reqOrigin = $_SERVER['HTTP_ORIGIN'];
    } else if (!empty($_SERVER['HTTP_REFERER'])) {
        $reqOrigin = $_SERVER['HTTP_REFERER'];
    }
    $reqHost = $reqOrigin ? parse_url($reqOrigin, PHP_URL_HOST) : '';
    if (!$siteHost || !$reqHost || strcasecmp($siteHost, $reqHost) !== 0) {
        http_response_code(403);
        exit('forbidden');
    }

    $newLang = isset($_POST['lang']) ? (string)$_POST['lang'] : '';
    if ($newLang !== '' && !isset(GI18n::$supportedLangs[$newLang])) {
        http_response_code(400);
        exit('invalid');
    }

    $db  = Typecho_Db::get();
    $row = $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'theme:G'));
    if (!$row) {
        http_response_code(404);
        exit('no-config');
    }
    $value = @unserialize($row['value'], ['allowed_classes' => false]);
    if (!is_array($value)) $value = array();
    $value['lang'] = $newLang;
    $db->query(
        $db->update('table.options')
           ->rows(array('value' => serialize($value)))
           ->where('name = ?', 'theme:G')
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => true, 'lang' => $newLang));
    exit;
}

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

/**
 * Inline CSS + JS for the theme settings admin page:
 *  - Styles native <select> elements to match other inputs / buttons.
 *  - Wires the language selector (name="lang") to autosave immediately on
 *    change via AJAX to `?GLangChange=1`, then reloads the page so that
 *    the new translations take effect.
 */
function gLangAutosaveAssets()
{
    // The handler at the top of this file (functions.php) only runs when
    // Typecho's front-controller (index.php) actually loads the active theme.
    // Hitting `<themeUrl>/?GLangChange=1` directly bypasses Typecho entirely
    // (it just hits the theme directory on the web server) and yields an
    // empty 200 response, so the change is never persisted.  Use the site
    // URL instead so the request is routed through index.php which then
    // includes functions.php and executes the save logic.
    $siteUrl  = rtrim(Helper::options()->siteUrl, '/');
    $endpoint = $siteUrl . '/?GLangChange=1';
    $savingText = addslashes(GI18n::t('config.lang_saving'));
    $savedText  = addslashes(GI18n::t('config.lang_saved'));
    $errorText  = addslashes(GI18n::t('config.lang_save_error'));
    ob_start();
    ?>
    <style>
    /* Bring native <select> in line with the rest of the admin UI */
    .typecho-option select,
    select.g-select {
        display: inline-block;
        box-sizing: border-box;
        padding: .55em .9em;
        padding-right: 2.2em;
        margin: 0;
        min-width: 12em;
        max-width: 100%;
        background-color: #fff;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'><path fill='%23313a46' d='M0 0l5 6 5-6z'/></svg>");
        background-repeat: no-repeat;
        background-position: right .8em center;
        background-size: 10px 6px;
        color: #43454a;
        font-size: 14px;
        line-height: 1.4;
        border: 1px solid #d6d6d6;
        border-bottom: 2px solid #444;
        border-radius: 6px;
        outline: 0;
        cursor: pointer;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .typecho-option select:hover,
    select.g-select:hover {
        border-color: #313a46;
    }
    .typecho-option select:focus,
    select.g-select:focus {
        border-color: #313a46;
        box-shadow: 0 0 0 3px rgba(49,58,70,0.12);
    }
    .g-lang-status {
        display: inline-block;
        margin-left: 10px;
        font-size: 12px;
        color: #6a6a6a;
        opacity: 0;
        transition: opacity .25s ease;
    }
    .g-lang-status.show { opacity: 1; }
    .g-lang-status.err  { color: #c0392b; }
    .g-lang-status.ok   { color: #2c8a4a; }
    </style>
    <script>
    (function(){
        function ready(fn){
            if (document.readyState !== 'loading') fn();
            else document.addEventListener('DOMContentLoaded', fn);
        }
        ready(function(){
            var select = document.querySelector('select[name="lang"]');
            if (!select) return;
            select.classList.add('g-select');

            var status = document.createElement('span');
            status.className = 'g-lang-status';
            select.parentNode.insertBefore(status, select.nextSibling);

            function setStatus(msg, cls){
                status.textContent = msg;
                status.className = 'g-lang-status show ' + (cls || '');
            }

            select.addEventListener('change', function(){
                var value = select.value;
                setStatus('<?php echo $savingText; ?>', '');
                var data = new FormData();
                data.append('lang', value);
                fetch(<?php echo json_encode($endpoint); ?>, {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin'
                }).then(function(resp){
                    if (!resp.ok) throw new Error('http ' + resp.status);
                    return resp.json();
                }).then(function(){
                    setStatus('<?php echo $savedText; ?>', 'ok');
                    // Reload so server-rendered translations refresh.
                    setTimeout(function(){ window.location.reload(); }, 400);
                }).catch(function(){
                    setStatus('<?php echo $errorText; ?>', 'err');
                });
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Inline CSS + JS for admin settings sectioning.
 *
 * Groups the otherwise-flat list of theme settings into collapsible
 * categories with anchored side-navigation, so the long settings page
 * becomes browsable.  Sections are defined as a map from a starting
 * field name (the FIRST input belonging to that section) to a section
 * label; everything from that input up to the next section starter is
 * considered part of that section.
 */
function gAdminSectionsAssets()
{
    // Field name → section key.  Each entry marks the *start* of a section.
    // The order here is also the order sections appear in the side nav.
    $sections = array(
        'lang'           => GI18n::t('section.basic'),
        'background'     => GI18n::t('section.appearance'),
        'defaultBanner'  => GI18n::t('section.banner'),
        'enableHeaderSearch' => GI18n::t('section.header'),
        'profileAvatar'  => GI18n::t('section.sidebar'),
        'sponsorIMG'     => GI18n::t('section.sponsor'),
        'enableIndexPage'=> GI18n::t('section.homepage'),
        'footerCustom'   => GI18n::t('section.footer'),
        'autoNightSpan'  => GI18n::t('section.night'),
        'commentType'    => GI18n::t('section.comment'),
        'enableDefaultTOC' => GI18n::t('section.article'),
        'customWidgets'  => GI18n::t('section.custom_code'),
        'enableLegacy'   => GI18n::t('section.legacy'),
        'advanceSetting' => GI18n::t('section.advanced'),
    );
    $sectionsJson = json_encode($sections, JSON_UNESCAPED_UNICODE);
    $tocTitle     = addslashes(GI18n::t('section.toc_title'));
    $collapseAll  = addslashes(GI18n::t('section.collapse_all'));
    $expandAll    = addslashes(GI18n::t('section.expand_all'));

    ob_start();
    ?>
    <style>
    /* Settings page side-nav layout. The nav floats to the left of the form
       so it can use `position: sticky` and follow the scroll, while leaving
       Typecho's markup untouched. */
    .typecho-page-main { position: relative; }
    body.g-sections-on .typecho-page-main::after { content: ''; display: block; clear: both; }
    @media (max-width: 900px) {
        body.g-sections-on .typecho-page-main > form,
        body.g-sections-on .typecho-page-main > div#backup { padding-left: 0; }
    }

    .g-section-nav {
        position: sticky;
        top: 20px;
        align-self: flex-start;
        width: 200px;
        max-height: calc(100vh - 40px);
        overflow-y: auto;
        font-size: 13px;
        border: 1px solid #e3e3e3;
        border-radius: 6px;
        background: #fafafa;
        padding: 10px 0;
        z-index: 5;
        float: left;
        margin-right: 20px;
    }
    body.g-sections-on .typecho-page-main > form,
    body.g-sections-on .typecho-page-main > div#backup { padding-left: 0; }
    body.g-sections-on .typecho-page-main { overflow: visible; }
    @media (max-width: 900px) {
        .g-section-nav {
            position: static;
            float: none;
            width: auto;
            margin: 0 0 1rem 0;
            max-height: none;
        }
    }
    .g-section-nav-title {
        font-weight: 600;
        padding: 4px 14px 8px;
        color: #313a46;
        border-bottom: 1px solid #e3e3e3;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .g-section-nav-toggle-all {
        font-size: 11px;
        font-weight: 400;
        color: #6a6a6a;
        cursor: pointer;
        user-select: none;
        padding: 2px 6px;
        border-radius: 3px;
    }
    .g-section-nav-toggle-all:hover { background: #ececec; color: #313a46; }
    .g-section-nav a {
        display: block;
        padding: 6px 14px;
        color: #43454a;
        text-decoration: none;
        border-left: 3px solid transparent;
        transition: background .15s ease, border-color .15s ease;
    }
    .g-section-nav a:hover { background: #f0f0f0; }
    .g-section-nav a.active {
        background: #fff;
        border-left-color: #313a46;
        color: #313a46;
        font-weight: 600;
    }

    .g-section {
        border: 1px solid #e3e3e3;
        border-radius: 6px;
        margin: 14px 0;
        background: #fff;
        scroll-margin-top: 60px;
    }
    .g-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        cursor: pointer;
        background: #f6f7f9;
        border-radius: 6px 6px 0 0;
        border-bottom: 1px solid #e3e3e3;
        user-select: none;
    }
    .g-section.collapsed .g-section-header { border-bottom: none; border-radius: 6px; }
    .g-section-header h3 {
        margin: 0;
        font-size: 15px;
        color: #313a46;
    }
    .g-section-toggle {
        font-size: 13px;
        color: #6a6a6a;
        transition: transform .2s ease;
    }
    .g-section.collapsed .g-section-toggle { transform: rotate(-90deg); }
    .g-section-body { padding: 4px 14px 10px; }
    .g-section.collapsed .g-section-body { display: none; }

    /* Legacy section: sub-options greyed out when master toggle is off */
    .g-section[data-section-key="enableLegacy"] .g-legacy-sub {
        opacity: 0.45;
        pointer-events: none;
        transition: opacity .2s ease;
    }
    .g-section[data-section-key="enableLegacy"].g-legacy-active .g-legacy-sub {
        opacity: 1;
        pointer-events: auto;
    }
    </style>
    <script>
    (function(){
        var SECTIONS = <?php echo $sectionsJson; ?>;
        var TOC_TITLE = '<?php echo $tocTitle; ?>';
        var TOGGLE_COLLAPSE_TEXT = '<?php echo $collapseAll; ?>';
        var TOGGLE_EXPAND_TEXT = '<?php echo $expandAll; ?>';

        function findFieldLi(name){
            // Typecho wraps each form item in <li class="typecho-option">; the
            // inner input/select/textarea carries the configured name attr.
            var el = document.querySelector('[name="' + name + '"]');
            if (!el) return null;
            var li = el.closest('li.typecho-option');
            return li || null;
        }

        function ready(fn){
            if (document.readyState !== 'loading') fn();
            else document.addEventListener('DOMContentLoaded', fn);
        }

        ready(function(){
            // The themeConfig page may contain multiple forms (Typecho's
            // main settings form + the backup form rendered by backup()).
            // Pick the one inside .typecho-page-main that actually contains
            // typecho-option items. This works for both old (form.protected)
            // and new Typecho versions (1.2+).
            var form = null;
            var candidates = document.querySelectorAll('.typecho-page-main form');
            for (var fi = 0; fi < candidates.length; fi++) {
                if (candidates[fi].querySelector('li.typecho-option')) { form = candidates[fi]; break; }
            }
            if (!form) return;
            // Find the <ul> that actually holds the typecho-option items.
            var firstLi = form.querySelector('li.typecho-option');
            if (!firstLi) return;
            var ul = firstLi.parentNode;

            // Build {sectionKey: {label, startLi}} list, in declared order.
            var ordered = [];
            Object.keys(SECTIONS).forEach(function(fieldName){
                var li = findFieldLi(fieldName);
                if (!li) return;
                ordered.push({ key: fieldName, label: SECTIONS[fieldName], startLi: li });
            });
            if (!ordered.length) return;

            document.body.classList.add('g-sections-on');

            // For each section, wrap the relevant <li> elements in a
            // <li class="g-section">… that contains a header + a nested <ul>.
            ordered.forEach(function(sec, idx){
                var nextStart = ordered[idx + 1] ? ordered[idx + 1].startLi : null;
                // Collect contiguous siblings from sec.startLi up to (but not
                // including) nextStart.
                var nodes = [];
                var cur = sec.startLi;
                while (cur && cur !== nextStart) {
                    var next = cur.nextElementSibling;
                    nodes.push(cur);
                    cur = next;
                }
                if (!nodes.length) return;

                // Build wrapping container.
                var wrapper = document.createElement('li');
                wrapper.className = 'g-section';
                wrapper.id = 'g-sec-' + sec.key;
                wrapper.setAttribute('data-section-key', sec.key);

                var header = document.createElement('div');
                header.className = 'g-section-header';
                var h3 = document.createElement('h3');
                h3.textContent = sec.label;
                var toggle = document.createElement('span');
                toggle.className = 'g-section-toggle';
                toggle.textContent = '▾';
                header.appendChild(h3);
                header.appendChild(toggle);

                var body = document.createElement('div');
                body.className = 'g-section-body';
                var innerUl = document.createElement('ul');
                innerUl.className = 'typecho-option-list';
                body.appendChild(innerUl);

                wrapper.appendChild(header);
                wrapper.appendChild(body);

                // Insert wrapper before the first node, then move nodes inside.
                ul.insertBefore(wrapper, nodes[0]);
                nodes.forEach(function(n){ innerUl.appendChild(n); });

                header.addEventListener('click', function(){
                    wrapper.classList.toggle('collapsed');
                });
            });

            // Build side-nav.
            var page = document.querySelector('.typecho-page-main');
            var navLinks = [];
            if (page) {
                var nav = document.createElement('nav');
                nav.className = 'g-section-nav';
                var title = document.createElement('div');
                title.className = 'g-section-nav-title';
                var titleLabel = document.createElement('span');
                titleLabel.textContent = TOC_TITLE;
                title.appendChild(titleLabel);
                var toggleAll = document.createElement('span');
                toggleAll.className = 'g-section-nav-toggle-all';
                toggleAll.textContent = TOGGLE_COLLAPSE_TEXT;
                toggleAll.setAttribute('data-state', 'expanded');
                title.appendChild(toggleAll);
                nav.appendChild(title);
                ordered.forEach(function(sec){
                    var a = document.createElement('a');
                    a.href = '#g-sec-' + sec.key;
                    a.textContent = sec.label;
                    a.setAttribute('data-sec-key', sec.key);
                    a.addEventListener('click', function(e){
                        e.preventDefault();
                        var target = document.getElementById('g-sec-' + sec.key);
                        if (!target) return;
                        // Expand if collapsed, then scroll to it.
                        target.classList.remove('collapsed');
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        nav.querySelectorAll('a').forEach(function(x){ x.classList.remove('active'); });
                        a.classList.add('active');
                    });
                    nav.appendChild(a);
                    navLinks.push(a);
                });
                page.insertBefore(nav, page.firstChild);

                // Expand / collapse all toggle.
                toggleAll.addEventListener('click', function(){
                    var state = toggleAll.getAttribute('data-state');
                    var collapse = state === 'expanded';
                    ordered.forEach(function(sec){
                        var t = document.getElementById('g-sec-' + sec.key);
                        if (!t) return;
                        if (collapse) t.classList.add('collapsed');
                        else t.classList.remove('collapsed');
                    });
                    toggleAll.setAttribute('data-state', collapse ? 'collapsed' : 'expanded');
                    toggleAll.textContent = collapse ? TOGGLE_EXPAND_TEXT : TOGGLE_COLLAPSE_TEXT;
                });

                // Scroll-spy: highlight the section closest to the top of the viewport.
                var scrollSpy = function(){
                    var bestKey = null;
                    var bestTop = -Infinity;
                    var anchor = 80; // px from top considered the "active" line
                    ordered.forEach(function(sec){
                        var t = document.getElementById('g-sec-' + sec.key);
                        if (!t) return;
                        var top = t.getBoundingClientRect().top;
                        if (top <= anchor && top > bestTop) {
                            bestTop = top;
                            bestKey = sec.key;
                        }
                    });
                    if (!bestKey && ordered.length) bestKey = ordered[0].key;
                    navLinks.forEach(function(l){
                        if (l.getAttribute('data-sec-key') === bestKey) l.classList.add('active');
                        else l.classList.remove('active');
                    });
                };
                var spyTicking = false;
                window.addEventListener('scroll', function(){
                    if (spyTicking) return;
                    spyTicking = true;
                    requestAnimationFrame(function(){
                        scrollSpy();
                        spyTicking = false;
                    });
                }, { passive: true });
                scrollSpy();
            }

            // Legacy section: grey out sub-options when enableLegacy is off.
            var legacySec = document.getElementById('g-sec-enableLegacy');
            if (legacySec) {
                var legacyRadios = legacySec.querySelectorAll('input[name="enableLegacy"]');
                var legacyBody = legacySec.querySelector('.g-section-body');
                if (legacyBody && legacyRadios.length) {
                    // Wrap all options except the enableLegacy toggle itself
                    var innerItems = legacyBody.querySelectorAll('li.typecho-option');
                    for (var li = 0; li < innerItems.length; li++) {
                        // Skip the enableLegacy field itself
                        if (innerItems[li].querySelector('[name="enableLegacy"]')) continue;
                        innerItems[li].classList.add('g-legacy-sub');
                    }
                    function syncLegacy() {
                        var checked = legacySec.querySelector('input[name="enableLegacy"]:checked');
                        if (checked && checked.value === '1') {
                            legacySec.classList.add('g-legacy-active');
                        } else {
                            legacySec.classList.remove('g-legacy-active');
                        }
                    }
                    syncLegacy();
                    for (var ri = 0; ri < legacyRadios.length; ri++) {
                        legacyRadios[ri].addEventListener('change', syncLegacy);
                    }
                }
            }
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

function themeConfig($form)
{
    echo "<link rel='stylesheet' href='".G::staticUrl('static/css/Admin/S.min.css')."'/>";
    echo gPalettePickerAssets();
    echo gLangAutosaveAssets();
    echo gAdminSectionsAssets();
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

    $copyrightFormat = new Typecho_Widget_Helper_Form_Element_Text('copyrightFormat', null, '', _t(GI18n::t('config.copyright_format')), _t(GI18n::t('config.copyright_format_desc')));
    $form->addInput($copyrightFormat);

    $cdn = new Typecho_Widget_Helper_Form_Element_Text('cdn', null, null, _t(GI18n::t('config.cdn')), _t(GI18n::t('config.cdn_desc')));
    $form->addInput($cdn);

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

    $themeShadow = new Typecho_Widget_Helper_Form_Element_Radio('themeShadow', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '1', _t(GI18n::t('config.theme_shadow')), _t(GI18n::t('config.theme_shadow_desc')));
    $form->addInput($themeShadow);

    $defaultBanner = new Typecho_Widget_Helper_Form_Element_Text('defaultBanner', null, null, _t(GI18n::t('config.default_banner')), _t(GI18n::t('config.default_banner_desc')));
    $form->addInput($defaultBanner);

    $autoBanner = new Typecho_Widget_Helper_Form_Element_Radio('autoBanner', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '1', _t(GI18n::t('config.auto_banner')), _t(GI18n::t('config.auto_banner_desc')));
    $form->addInput($autoBanner);

    $headerBackground = new Typecho_Widget_Helper_Form_Element_Text('headerBackground', null, null, _t(GI18n::t('config.header_bg')), _t(GI18n::t('config.header_bg_desc')));
    $form->addInput($headerBackground);

    $enableHeaderSearch = new Typecho_Widget_Helper_Form_Element_Radio('enableHeaderSearch', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '0', _t(GI18n::t('config.enable_search')), _t(GI18n::t('config.enable_search_desc')));
    $form->addInput($enableHeaderSearch);

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

    $sponsorIMG = new Typecho_Widget_Helper_Form_Element_Text('sponsorIMG', null, null, _t(GI18n::t('config.sponsor_img')), _t(GI18n::t('config.sponsor_img_desc')));
    $form->addInput($sponsorIMG);

    /* ===== Homepage style — independent toggles, replaces old radio ===== */
    $enableIndexPage = new Typecho_Widget_Helper_Form_Element_Radio('enableIndexPage', array(
        '1' => _t(GI18n::t('common.use')),
        '0' => _t(GI18n::t('common.not_use'))
    ), '0', _t(GI18n::t('config.enable_index_page')), _t(GI18n::t('config.enable_index_page_desc')));
    $form->addInput($enableIndexPage);

    $articleColumns = new Typecho_Widget_Helper_Form_Element_Text('articleColumns', null, '2', _t(GI18n::t('config.article_columns')), _t(GI18n::t('config.article_columns_desc')));
    $articleColumns->input->setAttribute('type', 'number');
    $articleColumns->input->setAttribute('min', '1');
    $articleColumns->input->setAttribute('max', '6');
    $articleColumns->input->setAttribute('step', '1');
    $form->addInput($articleColumns);

    $showArticleBanner = new Typecho_Widget_Helper_Form_Element_Radio('showArticleBanner', array(
        '1' => _t(GI18n::t('common.show')),
        '0' => _t(GI18n::t('common.hide'))
    ), '1', _t(GI18n::t('config.show_article_banner')), _t(GI18n::t('config.show_article_banner_desc')));
    $form->addInput($showArticleBanner);

    $showArticleExcerpt = new Typecho_Widget_Helper_Form_Element_Radio('showArticleExcerpt', array(
        '1' => _t(GI18n::t('common.show')),
        '0' => _t(GI18n::t('common.hide'))
    ), '1', _t(GI18n::t('config.show_article_excerpt')), _t(GI18n::t('config.show_article_excerpt_desc')));
    $form->addInput($showArticleExcerpt);

    $defaultArticlePath = new Typecho_Widget_Helper_Form_Element_Text('defaultArticlePath', null, 'index.php/blog', _t(GI18n::t('config.default_path')), _t(GI18n::t('config.default_path_desc')));
    $form->addInput($defaultArticlePath);
    /* ============================================================== */

    $footerCustom = new Typecho_Widget_Helper_Form_Element_Textarea('footerCustom', null, null, _t(GI18n::t('config.footer_custom')), _t(GI18n::t('config.footer_custom_desc')));
    $form->addInput($footerCustom);

    $autoNightSpan = new Typecho_Widget_Helper_Form_Element_Text('autoNightSpan', null, '23-6', _t(GI18n::t('config.auto_night_span')), _t(GI18n::t('config.auto_night_span_desc')));
    $form->addInput($autoNightSpan);

    $autoNightMode = new Typecho_Widget_Helper_Form_Element_Radio('autoNightMode', array(
        '3' => _t(GI18n::t('config.follow_system')),
        '2' => _t(GI18n::t('config.custom_time')),
        '1' => _t(GI18n::t('config.both_enabled')),
        '0' => _t(GI18n::t('common.disable'))
    ), '3', _t(GI18n::t('config.auto_night_mode')), _t(GI18n::t('config.auto_night_mode_desc')));
    $form->addInput($autoNightMode);

    $commentType = new Typecho_Widget_Helper_Form_Element_Radio('commentType', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '1', _t(GI18n::t('config.comment_switch')), _t(GI18n::t('config.comment_switch_desc')));
    $form->addInput($commentType);

    $enableDefaultTOC = new Typecho_Widget_Helper_Form_Element_Radio('enableDefaultTOC', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '0', _t(GI18n::t('config.enable_toc')), _t(GI18n::t('config.enable_toc_desc')));
    $form->addInput($enableDefaultTOC);

    $enableKatex = new Typecho_Widget_Helper_Form_Element_Radio('enableKatex', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '0', _t(GI18n::t('config.enable_katex')), _t(GI18n::t('config.enable_katex_desc')));
    $form->addInput($enableKatex);

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

    /* ===== Legacy section — old ICP / footer logos / UPYUN badge ===== */
    $enableLegacy = new Typecho_Widget_Helper_Form_Element_Radio('enableLegacy', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '0', _t(GI18n::t('config.enable_legacy')), _t(GI18n::t('config.enable_legacy_desc')));
    $form->addInput($enableLegacy);

    $icp = new Typecho_Widget_Helper_Form_Element_Text('icp', null, null, _t(GI18n::t('config.icp')), _t(GI18n::t('config.icp_desc')));
    $form->addInput($icp);

    $icpUrl = new Typecho_Widget_Helper_Form_Element_Text('icpUrl', null, 'https://beian.miit.gov.cn', _t(GI18n::t('config.icp_url')), _t(GI18n::t('config.icp_url_desc')));
    $form->addInput($icpUrl);

    $footerLOGO = new Typecho_Widget_Helper_Form_Element_Text('footerLOGO', null, null, _t(GI18n::t('config.footer_logo')), _t(GI18n::t('config.footer_logo_desc')));
    $form->addInput($footerLOGO);

    $enableUPYUNLOGO = new Typecho_Widget_Helper_Form_Element_Radio('enableUPYUNLOGO', array(
        '1' => _t(GI18n::t('common.enable')),
        '0' => _t(GI18n::t('common.disable'))
    ), '0', _t(GI18n::t('config.enable_upyun')), _t(GI18n::t('config.enable_upyun_desc')));
    $form->addInput($enableUPYUNLOGO);
    /* ============================================================ */

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



