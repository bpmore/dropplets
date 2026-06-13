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
    <p class="empty-state">No papers have been filed yet. The first abstract is forthcoming.</p>
<?php else: ?>
    <ol class="abstracts">
        <?php foreach ($allPosts as $p): ?>
            <li class="abstract">
                <article>
                    <h2 class="abstract-title">
                        <a href="<?= e(fn_post_url($router, $p)) ?>"><?= e($p['title']) ?></a>
                    </h2>
                    <?php if (!empty($p['password'])): ?>
                        <p class="abstract-text is-locked">&#128274; This entry is password-protected. The abstract is withheld.</p>
                    <?php elseif (($x = fn_excerpt($p, 180)) !== ''): ?>
                        <p class="abstract-text"><?= e($x) ?></p>
                    <?php endif; ?>
                    <p class="abstract-meta"><?= e($p['author']) ?> &middot; <?= e(date($dateFormat, (int) $p['date'])) ?></p>
                </article>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<?php fn_pagination($router, $page, $numPages); ?>
<?php require __DIR__ . '/footer.php'; ?>
