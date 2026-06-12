<?php
use function Fieldnote\e;
use function Fieldnote\fn_post_url;
use function Fieldnote\fn_pagination;
require __DIR__ . '/header.php';

$dateFormat = i18n('dateformat', false);
?>
<h1 class="sr-only"><?= e($siteName) ?></h1>
<?php if (empty($allPosts)): ?>
    <p class="empty-state">The nave is silent. No words have been set down yet.</p>
<?php else: ?>
    <div class="cloister">
        <?php foreach ($allPosts as $p): ?>
            <article class="arch">
                <div class="arch-body">
                    <?php if (!empty($p['password'])): ?>
                        <p class="arch-lock" aria-hidden="true">&#128274;</p>
                    <?php elseif ($p['imageUrl'] !== ''): ?>
                        <img class="arch-image" src="<?= e($p['imageUrl']) ?>" alt="" loading="lazy">
                    <?php endif; ?>
                    <h2 class="arch-title">
                        <a href="<?= e(fn_post_url($router, $p)) ?>"><?= e($p['title']) ?></a>
                    </h2>
                    <?php if (!empty($p['password'])): ?>
                        <p class="arch-meta">Kept under seal &middot; <?= e(date($dateFormat, (int) $p['date'])) ?></p>
                    <?php else: ?>
                        <p class="arch-meta"><?= e($p['author']) ?> &middot; <?= e(date($dateFormat, (int) $p['date'])) ?></p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php fn_pagination($router, $page, $numPages); ?>
<?php require __DIR__ . '/footer.php'; ?>
