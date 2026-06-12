<?php
use function Fieldnote\e;
use function Fieldnote\fn_post_url;
use function Fieldnote\fn_pagination;
require __DIR__ . '/header.php';

$dateFormat = i18n('dateformat', false);
?>
<h1 class="sr-only"><?= e($siteName) ?></h1>
<?php if (empty($allPosts)): ?>
    <p class="empty-state">No dates announced. Stay tuned.</p>
<?php else: ?>
    <div class="setlist">
        <?php foreach ($allPosts as $p): ?>
            <article class="row">
                <p class="row-date"><?= e(date($dateFormat, (int) $p['date'])) ?></p>
                <h2 class="row-title">
                    <a href="<?= e(fn_post_url($router, $p)) ?>"><?= e($p['title']) ?></a>
                    <?php if (!empty($p['password'])): ?>
                        <span class="row-tag">Protected</span>
                    <?php endif; ?>
                </h2>
                <p class="row-author"><?= !empty($p['password']) ? 'Members only' : e($p['author']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php fn_pagination($router, $page, $numPages); ?>
<?php require __DIR__ . '/footer.php'; ?>
