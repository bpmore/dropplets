<?php
use function Fieldnote\e;
use function Fieldnote\fn_post_url;
use function Fieldnote\fn_pagination;
require __DIR__ . '/header.php';
Fieldnote\fn_search_status($searchQuery ?? null, count($allPosts ?? []));

$dateFormat = i18n('dateformat', false);
?>
<h1 class="sr-only"><?= e($siteName) ?></h1>
<?php if (empty($allPosts)): ?>
    <p class="empty-state">Nothing on the press yet. Check back soon!</p>
<?php else: ?>
    <ol class="zine-list">
        <?php foreach ($allPosts as $p): ?>
            <li class="zine-entry">
                <article class="zine-row">
                    <div class="zine-body">
                        <h2 class="zine-title">
                            <a href="<?= e(fn_post_url($router, $p)) ?>"><?= e($p['title']) ?></a>
                        </h2>
                        <?php if (!empty($p['password'])): ?>
                            <p class="zine-meta"><span aria-hidden="true">&#128274;</span> Protected print &middot; <?= e(date($dateFormat, (int) $p['date'])) ?></p>
                        <?php else: ?>
                            <p class="zine-meta">By <?= e($p['author']) ?> &middot; <?= e(date($dateFormat, (int) $p['date'])) ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<?php fn_pagination($router, $page, $numPages); ?>
<?php require __DIR__ . '/footer.php'; ?>
