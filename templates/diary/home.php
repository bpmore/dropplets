<?php
use function Fieldnote\e;
use function Fieldnote\fn_post_url;
use function Fieldnote\fn_pagination;
require __DIR__ . '/header.php';

$dateFormat = i18n('dateformat', false);
?>
<h1 class="sr-only"><?= e($siteName) ?></h1>
<?php if (empty($allPosts)): ?>
    <p class="empty-state">The first page is still blank. Check back soon.</p>
<?php else: ?>
    <ol class="entries" reversed>
        <?php foreach ($allPosts as $p): ?>
            <li class="entry">
                <p class="entry-date" aria-hidden="true"><?= e(date($dateFormat, (int) $p['date'])) ?></p>
                <h2 class="entry-title">
                    <?php if (!empty($p['password'])): ?>
                        <span class="entry-lock" aria-hidden="true">&#128274;</span>
                    <?php endif; ?>
                    <a href="<?= e(fn_post_url($router, $p)) ?>"><?= e($p['title']) ?></a>
                </h2>
                <?php if (!empty($p['password'])): ?>
                    <p class="entry-meta">A private entry &middot; <?= e(date($dateFormat, (int) $p['date'])) ?></p>
                <?php else: ?>
                    <p class="entry-meta">Written by <?= e($p['author']) ?> &middot; <?= e(date($dateFormat, (int) $p['date'])) ?></p>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<?php fn_pagination($router, $page, $numPages); ?>
<?php require __DIR__ . '/footer.php'; ?>
