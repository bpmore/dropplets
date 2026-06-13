<?php
use function Fieldnote\e;
use function Fieldnote\fn_post_url;
use function Fieldnote\fn_excerpt;
use function Fieldnote\fn_pagination;
require __DIR__ . '/header.php';
Fieldnote\fn_search_status($searchQuery ?? null, count($allPosts ?? []));
$dateFormat = i18n('dateformat', false);
?>
<h1 class="sr-only"><?= e($siteName) ?></h1>
<?php if (empty($allPosts)): ?>
    <p class="empty-state">Nothing here yet.</p>
<?php else: ?>
    <?php foreach ($allPosts as $p): ?>
        <article class="row-entry">
            <div class="row-meta">
                <time><?= e(date($dateFormat, (int) $p['date'])) ?></time>
                <span class="row-author"><?= e($p['author']) ?></span>
            </div>
            <div class="row-body">
                <h2 class="entry-title"><a href="<?= e(fn_post_url($router, $p)) ?>"><?= e($p['title']) ?></a></h2>
                <?php if (!empty($p['password'])): ?>
                    <p class="entry-excerpt">&#128274; This post is password-protected.</p>
                <?php elseif (($x = fn_excerpt($p, 200)) !== ''): ?>
                    <p class="entry-excerpt"><?= e($x) ?></p>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
<?php fn_pagination($router, $page, $numPages); ?>
<?php require __DIR__ . '/footer.php'; ?>
