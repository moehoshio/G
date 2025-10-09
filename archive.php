<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('components/header.php');
?>

<div id="container">
    <h3 class="archive-title"><?php $this->archiveTitle(array(
            'category' => _t('分類 %s 下的文章'),
            'search' => _t('包含關鍵字 %s 的文章'),
            'tag' => _t('標籤 %s 下的文章'),
            'author' => _t('%s 發布的文章')
        ), '', ''); ?></h3>
    <?php if ($this->have()): ?>
        <div id="articles">
            <?php $this->need('components/article.php'); ?>
        </div>
        <div id="articles-switch" class="clear">
            <?php $this->pageLink('下一頁 >', 'next'); ?>
            <?php $this->pageLink('< 上一頁', 'prev'); ?>
        </div>
    <?php else: ?>
        <article class="post">
            <h2 class="post-title"><?php _e('沒有找到內容'); ?></h2>
        </article>
    <?php endif; ?>
</div>

<?php $this->need('components/footer.php'); ?>
