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
        inset: 0;
        background-size: cover;
        background-position: center;
        opacity: 0.35;
        z-index: 0;
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

    <div class="modern-link-grid">
        <?php if (isset($this->options->plugins['activated']['Links'])) : ?>
            <?php
            Links_Plugin::output('
                <a target="_blank" rel="noopener noreferrer" href="{url}" class="modern-link-card" title="{name}">
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

<?php if ($this->fields->enableComment == 1): ?>
    <?php $this->need('components/comments.php'); ?>
<?php endif; ?>
<?php $this->need('components/footer.php'); ?>
