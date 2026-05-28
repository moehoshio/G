<?php
/**
 * 友情链接 - 现代
 *
 * @package custom
 */
$this->need('components/header.php');
?>

<style>
    /* ===== Modern Link Page (scoped) ===== */
    #link-modern {
        max-width: 1200px;
        margin: 32px auto;
        padding: 0 20px;
    }

    #link-modern .modern-link-hero {
        position: relative;
        border-radius: var(--modern-radius, 20px);
        overflow: hidden;
        margin-bottom: 36px;
        background: var(--gradient-primary, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
        box-shadow: 0 12px 40px rgba(102, 126, 234, 0.25);
        isolation: isolate;
    }

    #link-modern .modern-link-hero::before,
    #link-modern .modern-link-hero::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
        opacity: 0.55;
        z-index: 0;
        pointer-events: none;
    }

    #link-modern .modern-link-hero::before {
        width: 320px;
        height: 320px;
        background: var(--gradient-accent, linear-gradient(135deg, #f093fb 0%, #f5576c 100%));
        top: -120px;
        right: -80px;
    }

    #link-modern .modern-link-hero::after {
        width: 260px;
        height: 260px;
        background: var(--gradient-warm, linear-gradient(135deg, #fa709a 0%, #fee140 100%));
        bottom: -120px;
        left: -60px;
    }

    #link-modern .modern-link-hero-banner {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        /* Cap banner height so expanding collapse boxes does not stretch the image */
        height: 420px;
        max-height: 100%;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        opacity: 0.35;
        z-index: 0;
        /* Soft fade to gradient at the bottom edge */
        -webkit-mask-image: linear-gradient(to bottom, #000 70%, transparent 100%);
        mask-image: linear-gradient(to bottom, #000 70%, transparent 100%);
        pointer-events: none;
    }

    #link-modern .modern-link-hero-inner {
        position: relative;
        z-index: 1;
        padding: 56px 40px;
        color: #fff;
        text-align: center;
    }

    #link-modern .modern-link-hero-inner h1 {
        margin: 0 0 12px;
        font-size: 2.2rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        background: linear-gradient(135deg, #ffffff 0%, rgba(255, 255, 255, 0.85) 100%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        text-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
    }

    #link-modern .modern-link-hero-inner .modern-link-desc {
        color: rgba(255, 255, 255, 0.92);
        line-height: 1.7;
        font-size: 1rem;
        max-width: 760px;
        margin: 0 auto;
    }

    #link-modern .modern-link-hero-inner .modern-link-desc a {
        color: #fff;
        text-decoration: underline;
        text-decoration-color: rgba(255, 255, 255, 0.5);
        text-underline-offset: 3px;
    }

    /* ===== Collapse / details inside hero (fix invisible white-on-white text) ===== */
    #link-modern .modern-link-hero-inner .collapse-box {
        margin: 1.5rem 0;
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.12);
        -webkit-backdrop-filter: blur(8px) saturate(160%);
        backdrop-filter: blur(8px) saturate(160%);
        overflow: hidden;
    }

    #link-modern .modern-link-hero-inner .collapse-title {
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
        cursor: pointer;
        position: relative;
        transition: background 0.25s ease;
    }

    #link-modern .modern-link-hero-inner .collapse-title:hover {
        background: rgba(255, 255, 255, 0.28);
    }

    #link-modern .modern-link-hero-inner .collapse-title p {
        color: #fff;
        margin: 0 !important;
        padding: 14px 44px 14px 18px;
        font-weight: 600;
        text-align: left;
    }

    #link-modern .modern-link-hero-inner .collapse-title::after {
        content: '▾';
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%) rotate(-90deg);
        color: rgba(255, 255, 255, 0.85);
        transition: transform 0.25s ease;
        font-size: 0.9rem;
    }

    #link-modern .modern-link-hero-inner .collapse-box[data-collapsed="false"] .collapse-title::after {
        transform: translateY(-50%) rotate(0);
    }

    #link-modern .modern-link-hero-inner .collapse-content-inner {
        margin: 1rem 1.25rem;
        color: rgba(255, 255, 255, 0.92);
        text-align: left;
    }

    /* ===== Code blocks inside hero — match modern glass style ===== */
    #link-modern .modern-link-hero-inner code {
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
        padding: 0.1em 0.4em;
        border-radius: 6px;
        font-size: 0.9em;
        border: 1px solid rgba(255, 255, 255, 0.15);
    }

    #link-modern .modern-link-hero-inner pre {
        background: rgba(0, 0, 0, 0.32) !important;
        color: rgba(255, 255, 255, 0.95);
        padding: 14px 18px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        -webkit-backdrop-filter: blur(8px);
        backdrop-filter: blur(8px);
        overflow-x: auto;
        text-align: left;
        margin: 1rem 0;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.04);
    }

    #link-modern .modern-link-hero-inner pre code {
        background: transparent;
        border: none;
        padding: 0;
        color: inherit;
        font-size: 0.9rem;
    }

    /* ===== Category toolbar & sections ===== */
    #link-modern .modern-link-toolbar {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    #link-modern .modern-link-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        font-size: 0.9rem;
        font-weight: 500;
        border-radius: 999px;
        cursor: pointer;
        color: #fff;
        background: var(--gradient-primary, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 6px 18px rgba(102, 126, 234, 0.28);
        transition: var(--transition-smooth, all 0.3s cubic-bezier(0.4, 0, 0.2, 1));
        -webkit-user-select: none;
        user-select: none;
    }

    #link-modern .modern-link-toggle:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(102, 126, 234, 0.35);
    }

    #link-modern .modern-link-toggle .modern-link-toggle-icon {
        font-size: 1rem;
        line-height: 1;
    }

    #link-modern .modern-link-section {
        margin-bottom: 30px;
    }

    #link-modern .modern-link-section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0 0 14px;
        padding: 0 4px 10px;
        font-size: 1.15rem;
        font-weight: 600;
        color: inherit;
        border-bottom: 1px solid rgba(102, 126, 234, 0.18);
    }

    #link-modern .modern-link-section-title .modern-link-section-count {
        font-size: 0.8rem;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.45);
        padding: 2px 10px;
        border-radius: 999px;
        background: rgba(102, 126, 234, 0.12);
    }

    #link-modern .modern-link-section.is-lost .modern-link-section-title {
        border-bottom-color: rgba(245, 87, 108, 0.35);
    }

    #link-modern .modern-link-section.is-lost .modern-link-section-count {
        background: rgba(245, 87, 108, 0.15);
        color: #c23457;
    }

    #link-modern .modern-link-section.is-lost .modern-link-card {
        filter: grayscale(0.65);
        opacity: 0.85;
    }

    #link-modern .modern-link-section.is-lost .modern-link-card:hover {
        filter: grayscale(0);
        opacity: 1;
    }

    /* ===== Card Grid ===== */
    #link-modern .modern-link-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 22px;
    }

    #link-modern .modern-link-card {
        position: relative;
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 18px 20px;
        border-radius: var(--modern-radius, 20px);
        background: var(--glass-bg, rgba(255, 255, 255, 0.65));
        -webkit-backdrop-filter: var(--glass-blur, blur(12px)) saturate(180%);
        backdrop-filter: var(--glass-blur, blur(12px)) saturate(180%);
        border: 1px solid var(--glass-border, rgba(255, 255, 255, 0.3));
        box-shadow: var(--glass-shadow, 0 8px 32px rgba(31, 38, 135, 0.12));
        color: inherit;
        text-decoration: none;
        overflow: hidden;
        transition: var(--transition-smooth, all 0.3s cubic-bezier(0.4, 0, 0.2, 1));
    }

    #link-modern .modern-link-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: var(--gradient-primary, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
        opacity: 0;
        transition: opacity 0.35s ease;
        z-index: 0;
        pointer-events: none;
    }

    #link-modern .modern-link-card > * {
        position: relative;
        z-index: 1;
    }

    #link-modern .modern-link-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 40px rgba(102, 126, 234, 0.28), var(--glow-primary, 0 0 20px rgba(102, 126, 234, 0.3));
        border-color: rgba(102, 126, 234, 0.45);
    }

    #link-modern .modern-link-card:hover::before {
        opacity: 0.92;
    }

    #link-modern .modern-link-card:hover .modern-link-name,
    #link-modern .modern-link-card:hover .modern-link-description {
        color: #fff;
    }

    #link-modern .modern-link-card:hover .modern-link-avatar {
        transform: rotate(-6deg) scale(1.06);
        border-color: rgba(255, 255, 255, 0.6);
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
    }

    /* ===== Avatar ===== */
    #link-modern .modern-link-avatar {
        flex: 0 0 56px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        object-fit: cover;
        background: #eee;
        border: 2px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        transition: var(--transition-smooth, all 0.3s cubic-bezier(0.4, 0, 0.2, 1));
    }

    /* ===== Text ===== */
    #link-modern .modern-link-body {
        flex: 1 1 auto;
        min-width: 0;
    }

    #link-modern .modern-link-name {
        margin: 0 0 4px;
        font-size: 1.05rem;
        font-weight: 600;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: color 0.3s ease;
    }

    #link-modern .modern-link-description {
        margin: 0;
        font-size: 0.85rem;
        line-height: 1.5;
        color: rgba(0, 0, 0, 0.6);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        transition: color 0.3s ease;
    }

    /* ===== Arrow indicator ===== */
    #link-modern .modern-link-arrow {
        flex: 0 0 auto;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(102, 126, 234, 0.12);
        color: #667eea;
        font-size: 0.9rem;
        opacity: 0;
        transform: translateX(-6px);
        transition: var(--transition-smooth, all 0.3s cubic-bezier(0.4, 0, 0.2, 1));
    }

    #link-modern .modern-link-card:hover .modern-link-arrow {
        opacity: 1;
        transform: translateX(0);
        background: rgba(255, 255, 255, 0.25);
        color: #fff;
    }

    /* ===== Empty / fallback ===== */
    #link-modern .modern-link-empty {
        text-align: center;
        padding: 48px 16px;
        border-radius: var(--modern-radius, 20px);
        background: var(--glass-bg, rgba(255, 255, 255, 0.65));
        border: 1px dashed rgba(102, 126, 234, 0.35);
        color: rgba(0, 0, 0, 0.6);
    }

    /* ===== Dark mode ===== */
    html[data-theme="dark"] #link-modern .modern-link-card,
    .dark #link-modern .modern-link-card {
        background: rgba(30, 32, 44, 0.55);
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);
    }

    html[data-theme="dark"] #link-modern .modern-link-name,
    .dark #link-modern .modern-link-name {
        color: rgba(255, 255, 255, 0.92);
    }

    html[data-theme="dark"] #link-modern .modern-link-description,
    .dark #link-modern .modern-link-description {
        color: rgba(255, 255, 255, 0.65);
    }

    html[data-theme="dark"] #link-modern .modern-link-avatar,
    .dark #link-modern .modern-link-avatar {
        border-color: rgba(255, 255, 255, 0.15);
    }

    html[data-theme="dark"] #link-modern .modern-link-empty,
    .dark #link-modern .modern-link-empty {
        background: rgba(30, 32, 44, 0.55);
        color: rgba(255, 255, 255, 0.7);
    }

    html[data-theme="dark"] #link-modern .modern-link-section-title,
    .dark #link-modern .modern-link-section-title {
        color: rgba(255, 255, 255, 0.92);
        border-bottom-color: rgba(255, 255, 255, 0.12);
    }

    html[data-theme="dark"] #link-modern .modern-link-section-count,
    .dark #link-modern .modern-link-section-count {
        color: rgba(255, 255, 255, 0.7);
        background: rgba(255, 255, 255, 0.08);
    }

    /* ===== Responsive ===== */
    @media (max-width: 600px) {
        #link-modern {
            padding: 0 14px;
            margin: 20px auto;
        }

        #link-modern .modern-link-hero-inner {
            padding: 40px 22px;
        }

        #link-modern .modern-link-hero-inner h1 {
            font-size: 1.6rem;
        }

        #link-modern .modern-link-grid {
            grid-template-columns: 1fr;
            gap: 14px;
        }
    }

    /* Respect reduced motion */
    @media (prefers-reduced-motion: reduce) {
        #link-modern .modern-link-card,
        #link-modern .modern-link-avatar,
        #link-modern .modern-link-arrow {
            transition: none;
        }

        #link-modern .modern-link-card:hover {
            transform: none;
        }
    }
</style>

<div id="link-modern">
    <section class="modern-link-hero" itemscope itemtype="http://schema.org/BlogPosting">
        <?php $img = G::getArticleFieldsBanner($this);
        if ($img != 'none'): ?>
            <div class="modern-link-hero-banner" style="background-image: url('<?php echo $img; ?>');"></div>
        <?php endif; ?>
        <div class="modern-link-hero-inner">
            <h1 itemprop="name headline"><?php $this->title(); ?></h1>
            <div class="modern-link-desc" itemprop="articleBody">
                <?php echo G::analyzeContent($this->content); ?>
            </div>
        </div>
    </section>

    <div class="modern-link-toolbar" hidden>
        <button type="button" class="modern-link-toggle" id="modernLinkGroupToggle" aria-pressed="false">
            <span class="modern-link-toggle-icon" aria-hidden="true">📁</span>
            <span class="modern-link-toggle-text">按分類顯示</span>
        </button>
    </div>

    <div class="modern-link-grid" id="modernLinkGrid">
        <?php if (isset($this->options->plugins['activated']['Links'])) : ?>
            <?php
            Links_Plugin::output('
                <a target="_blank" rel="noopener noreferrer" href="{url}" class="modern-link-card" title="{name}" data-sort="{sort}">
                    <img class="modern-link-avatar" src="{image}" alt="{name}" loading="lazy" onerror="this.style.visibility=\'hidden\'" />
                    <div class="modern-link-body">
                        <h4 class="modern-link-name">{name}</h4>
                        <p class="modern-link-description">{description}</p>
                    </div>
                    <span class="modern-link-arrow" aria-hidden="true">→</span>
                </a>', 0);
            ?>
        <?php else: ?>
            <div class="modern-link-empty">请启用 Links 插件以显示友情链接</div>
        <?php endif; ?>
    </div>
</div>

<script>
    (function () {
        var STORAGE_KEY = 'modernLinkGrouped';
        var LOST_PATTERN = /失\s*[联聯]|lost|offline|broken|下\s*[线線]|dead/i;

        function init() {
            var grid = document.getElementById('modernLinkGrid');
            var toolbar = document.querySelector('#link-modern .modern-link-toolbar');
            var toggle = document.getElementById('modernLinkGroupToggle');
            if (!grid || !toolbar || !toggle) return;

            var cards = Array.prototype.slice.call(grid.querySelectorAll('.modern-link-card'));
            if (!cards.length) return;

            // Snapshot the original flat order so we can restore it.
            var originalOrder = cards.slice();

            // Group by sort name.
            var groupsMap = {};
            var groupOrder = [];
            var lostCards = [];
            cards.forEach(function (card) {
                var sort = (card.getAttribute('data-sort') || '').trim();
                if (sort && LOST_PATTERN.test(sort)) {
                    lostCards.push(card);
                    return;
                }
                var key = sort || '__uncategorized__';
                if (!groupsMap[key]) {
                    groupsMap[key] = [];
                    groupOrder.push(key);
                }
                groupsMap[key].push(card);
            });

            var hasCategories = groupOrder.length > 1
                || (groupOrder.length === 1 && groupOrder[0] !== '__uncategorized__');
            var hasLost = lostCards.length > 0;

            // Show toolbar only when there is something to toggle.
            if (hasCategories) {
                toolbar.hidden = false;
            }

            function buildSection(title, items, extraClass) {
                var section = document.createElement('section');
                section.className = 'modern-link-section' + (extraClass ? ' ' + extraClass : '');
                var h = document.createElement('h3');
                h.className = 'modern-link-section-title';
                h.appendChild(document.createTextNode(title));
                var count = document.createElement('span');
                count.className = 'modern-link-section-count';
                count.textContent = items.length;
                h.appendChild(count);
                section.appendChild(h);
                var g = document.createElement('div');
                g.className = 'modern-link-grid';
                items.forEach(function (c) { g.appendChild(c); });
                section.appendChild(g);
                return section;
            }

            function renderFlat() {
                // Restore original flat layout, lost links appended at end.
                grid.innerHTML = '';
                // Remove any previously rendered sections after the grid.
                removeRenderedSections();
                originalOrder.forEach(function (c) {
                    if (lostCards.indexOf(c) === -1) grid.appendChild(c);
                });
                if (hasLost) {
                    var lostSection = buildSection('失聯星球', lostCards, 'is-lost modern-link-rendered-section');
                    grid.parentNode.insertBefore(lostSection, grid.nextSibling);
                }
            }

            function renderGrouped() {
                removeRenderedSections();
                grid.innerHTML = '';
                grid.style.display = 'none';
                groupOrder.forEach(function (key) {
                    var title = key === '__uncategorized__' ? '未分類' : key;
                    var section = buildSection(title, groupsMap[key], 'modern-link-rendered-section');
                    grid.parentNode.insertBefore(section, grid.nextSibling);
                });
                if (hasLost) {
                    var lostSection = buildSection('失聯星球', lostCards, 'is-lost modern-link-rendered-section');
                    grid.parentNode.insertBefore(lostSection, grid.nextSibling);
                }
            }

            function removeRenderedSections() {
                var rendered = document.querySelectorAll('#link-modern .modern-link-rendered-section');
                Array.prototype.forEach.call(rendered, function (el) { el.parentNode.removeChild(el); });
                grid.style.display = '';
            }

            function applyState(grouped) {
                toggle.setAttribute('aria-pressed', grouped ? 'true' : 'false');
                var textEl = toggle.querySelector('.modern-link-toggle-text');
                var iconEl = toggle.querySelector('.modern-link-toggle-icon');
                if (grouped && hasCategories) {
                    if (textEl) textEl.textContent = '平鋪顯示';
                    if (iconEl) iconEl.textContent = '🗂';
                    renderGrouped();
                } else {
                    if (textEl) textEl.textContent = '按分類顯示';
                    if (iconEl) iconEl.textContent = '📁';
                    renderFlat();
                }
            }

            // If grouping is unavailable, only the lost section will appear; hide the toggle.
            if (!hasCategories) {
                toggle.style.display = 'none';
            }

            var stored = null;
            try { stored = localStorage.getItem(STORAGE_KEY); } catch (e) {}
            var initialGrouped = hasCategories && stored === '1';
            applyState(initialGrouped);

            toggle.addEventListener('click', function () {
                if (!hasCategories) return;
                var nowGrouped = toggle.getAttribute('aria-pressed') !== 'true';
                applyState(nowGrouped);
                try { localStorage.setItem(STORAGE_KEY, nowGrouped ? '1' : '0'); } catch (e) {}
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
        // Re-initialise after pjax navigation.
        document.addEventListener('pjax:complete', init);
    })();
</script>

<?php if ($this->fields->enableComment == 1): ?>
    <?php $this->need('components/comments.php'); ?>
<?php endif; ?>
<?php $this->need('components/footer.php'); ?>
