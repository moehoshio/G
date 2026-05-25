<?php
/**
 * a graceful typecho theme
 *
 * @package G
 * @author 季悠然
 * @version 3.4.1
 * @link https://mitsuha.space
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('components/header.php');
?>
<?php
$articleColumns = (int) $this->options->articleColumns;
if ($articleColumns < 1) $articleColumns = 2;
if ($articleColumns > 6) $articleColumns = 6;
?>
<div id="container">
    <div id="articles" style="--article-columns: <?php echo $articleColumns; ?>;" data-columns="<?php echo $articleColumns; ?>">
        <?php $this->need('components/article.php'); ?>
    </div>
    <div id="articles-switch" class="articles-switch clear">
        <?php $this->pageLink(GI18n::t('pagination.next'), 'next'); ?>
        <?php $this->pageLink(GI18n::t('pagination.prev'), 'prev'); ?>
    </div>
</div>
<?php $this->need('components/footer.php'); ?>
