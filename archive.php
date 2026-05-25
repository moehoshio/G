<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('components/header.php');
?>

<div id="container">
    <h3 class="archive-title"><?php $this->archiveTitle(array(
            'category' => _t(GI18n::t('archive.category')),
            'search' => _t(GI18n::t('archive.search')),
            'tag' => _t(GI18n::t('archive.tag')),
            'author' => _t(GI18n::t('archive.author'))
        ), '', ''); ?></h3>
    <?php if ($this->have()): ?>
        <div id="articles">
            <?php $this->need('components/article.php'); ?>
        </div>
        <div id="articles-switch" class="articles-switch clear">
            <?php $this->pageLink(GI18n::t('pagination.next'), 'next'); ?>
            <?php $this->pageLink(GI18n::t('pagination.prev'), 'prev'); ?>
        </div>
    <?php else: ?>
        <article class="post">
            <h2 class="post-title"><?php _e(GI18n::t('archive.not_found')); ?></h2>
        </article>
    <?php endif; ?>
</div>

<?php $this->need('components/footer.php'); ?>
