<?php
/**
 * Homepage article list — unified layout.
 *
 * Visual layout is controlled by three independent options (set in the
 * theme admin panel under "首页样式" / "Homepage Style"):
 *   - articleColumns       (int >= 1)  Number of columns to display.  When
 *                                      set to 1 the items render full-width
 *                                      (the former "single column / 大图"
 *                                      mode).
 *   - showArticleBanner    (1/0)       Whether to render the post banner
 *                                      image.  Banner is rendered full
 *                                      width when columns == 1, and as a
 *                                      decorative side image otherwise.
 *   - showArticleExcerpt   (1/0)       Whether to render the post excerpt.
 *
 * The old fixed-choice picker (articleStyle: 0/1/2) has been removed.  The
 * former "大图" card-style mode (articleStyle == 2) is gone; its visual
 * role is now filled by the single-column mode (articleColumns == 1).
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$columns = (int) $this->options->articleColumns;
if ($columns < 1) $columns = 2;
if ($columns > 6) $columns = 6;

$showBanner  = ((string) $this->options->showArticleBanner) !== '0';
$showExcerpt = ((string) $this->options->showArticleExcerpt) !== '0';

$isSingle = ($columns === 1);
?>
<?php while ($this->next()): ?>
    <div class="article-item<?php echo $isSingle ? ' article-item-single' : ''; ?>"<?php echo $isSingle ? ' style="width: 100%;"' : ''; ?>>
        <?php $banner = $showBanner ? G::getArticleBanner($this) : 'none'; ?>
        <?php if ($isSingle && $banner !== 'none'): ?>
            <div style="background-image: url(<?php echo $banner; ?>);" class="article-banner article-banner-full"></div>
        <?php endif; ?>
        <h2 class="article-title"><a href="<?php $this->permalink(); ?>"><?php $this->title() ?></a></h2>
        <?php if ($showExcerpt): ?>
            <p><?php $this->excerpt(50); ?></p>
        <?php endif; ?>
        <div class="article-data">
            <span><?php $this->category(); ?></span>
            <span><?php $this->date('Y-m-d'); ?></span>
        </div>
        <?php if (!$isSingle && $banner !== 'none'): ?>
            <div class="article-banner-wrap"></div>
            <div style="background-image: url(<?php echo $banner; ?>);" class="article-banner"></div>
        <?php endif; ?>
    </div>
<?php endwhile; ?>
